/* Exécution : node tests/admin-script-test.js */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/admin.js'), 'utf8');

function element() {
	return {
		disabled: false, hidden: true, checked: false, dataset: {}, textContent: '', value: 0,
		listeners: {}, attributes: {},
		classList: { toggle() {}, add() {}, remove() {} },
		addEventListener(name, callback) { this.listeners[name] = callback; },
		setAttribute(name, value) { this.attributes[name] = value; },
		removeAttribute(name) { delete this.attributes[name]; },
	};
}

async function scenario({ failed = '', remove = false, startFailure = false, activationFailure = false, addon = false, theme = false, onlyTheme = false, beforeSubmit } = {}) {
	const ids = {};
	for (const id of ['wsp-installation-form', 'wsp-install-button', 'wsp-selection-count', 'wsp-remove-after-install', 'wsp-result', 'wsp-progress', 'wsp-progress-bar', 'wsp-extensions-link', 'wsp-select-all-plugins', 'wsp-select-all-themes']) ids[id] = element();
	const slugs = ['wordfence', 'elementor', 'wordpress-seo', 'wpforms-lite', 'google-site-kit'];
	if (addon) slugs.push('elementskit-lite');
	const toggles = slugs.map((slug, index) => {
		const input = element();
		input.value = slug;
		input.checked = !onlyTheme && (addon ? slug === 'elementskit-lite' : index < 2);
		input.dataset = { type: 'plugin', dependency: slug === 'elementskit-lite' ? 'elementor' : '' };
		input.disabled = index === 4;
		const status = element();
		const row = element();
		row.querySelector = () => status;
		input.closest = () => row;
		input.status = status;
		return input;
	});
	const themes = theme ? [Object.assign(element(), { value: 'astra', checked: true, dataset: { type: 'theme' }, closest: () => toggles[0].closest() })] : [];
	const actions = toggles.map((input, index) => Object.assign(element(), { dataset: { slug: input.value, installed: index === 4 ? 'true' : 'false', allowed: 'true' } }));
	ids['wsp-installation-form'].querySelectorAll = (selector) => selector.includes('plugins') ? toggles : selector.includes('themes') ? themes : selector === '.wsp-configure-button' ? actions : [];
	ids['wsp-remove-after-install'].checked = remove;
	const calls = [];
	const submissions = [];
	const messages = Object.fromEntries(['networkError', 'choose', 'installing', 'installed', 'failed', 'partial', 'done', 'deleted', 'finalizing', 'activating'].map((key) => [key, key]));
	const redirects = [];
	vm.runInNewContext(source, {
		document: { getElementById: (id) => ids[id] },
		window: { addEventListener() {}, location: { assign: (url) => redirects.push(url) } }, URLSearchParams,
		wpStarterPack: { ajaxUrl: '/ajax', nonce: 'nonce', activationNonce: 'activation-nonce', messages },
		fetch: async (url, options) => {
			assert.equal(ids['wsp-select-all-plugins'].disabled, true, 'Sélection globale bloquée pendant une opération');
			assert.equal(ids['wsp-select-all-themes'].disabled, true);
			const action = options.body.get('action').replace('wp_starter_pack_', '');
			const slug = options.body.get('slug');
			calls.push({ action, slug });
			submissions.push(options.body);
			if (action === 'activate') assert.equal(options.body.get('nonce'), 'activation-nonce');
			const fail = (action === 'install' && slug === failed) || (action === 'start' && startFailure) || (action === 'activate' && activationFailure);
			return { ok: !fail, json: async () => ({ success: !fail, data: fail ? { message: 'test failure' } : action === 'start' ? { token: 'session' } : action === 'finish' ? { deleted: remove } : action === 'activate' ? { url: '/admin/configuration' } : { installed: true } }) };
		},
	});
	if (beforeSubmit) beforeSubmit(toggles, { themes, ids });
	await ids['wsp-installation-form'].listeners.submit({ preventDefault() {} });
	return { ids, toggles, calls, actions, redirects, themes, submissions };
}

(async () => {
	let run = await scenario();
	assert.deepEqual(run.calls.map(({ action }) => action), ['start', 'install', 'install', 'finish']);
	assert.equal(run.toggles[0].disabled, true);
	assert.equal(run.toggles[2].disabled, false, 'Un module non sélectionné reste disponible après l’opération');
	assert.equal(run.toggles[4].disabled, true, 'Un module déjà installé reste indisponible');
	assert.equal(run.ids['wsp-result'].textContent, 'done');
	assert.equal(run.actions[0].hidden, false, 'Action disponible dès la fin de l’installation');
	assert.equal(run.actions[2].hidden, true, 'Pas d’activation proposée pour un module absent');
	await run.actions[0].listeners.click();
	assert.equal(run.calls.at(-1).action, 'activate');
	assert.deepEqual(run.redirects, ['/admin/configuration']);
	run = await scenario({ failed: 'wordfence', remove: true });
	assert.equal(run.calls.some(({ action }) => action === 'finish'), false, 'Aucune finalisation en cas d’échec');
	assert.equal(run.toggles[0].disabled, false);
	assert.equal(run.toggles[0].checked, true, 'Le module en échec reste sélectionné pour réessayer');
	assert.equal(run.toggles[1].disabled, true);
	assert.equal(run.ids['wsp-result'].textContent, 'partial');
	assert.equal(run.actions[0].hidden, true);
	assert.equal(run.actions[1].hidden, false, 'Module réussi activable malgré un échec partiel');
	run = await scenario({ remove: true });
	assert.equal(run.ids['wsp-result'].textContent, 'deleted');
	assert.equal(run.ids['wsp-install-button'].disabled, true);
	assert.ok(run.toggles.every((input) => input.disabled));
	assert.ok(run.actions.every((action) => action.hidden));
	run = await scenario({ startFailure: true });
	assert.equal(run.calls.length, 1);
	assert.equal(run.toggles[0].disabled, false);
	assert.equal(run.ids['wsp-install-button'].disabled, false);
	assert.equal(run.ids['wsp-result'].textContent, 'test failure');
	run = await scenario({ activationFailure: true });
	await run.actions[0].listeners.click();
	assert.deepEqual(run.redirects, [], 'Pas de redirection après une erreur d’activation');
	assert.equal(run.ids['wsp-result'].textContent, 'test failure');
	assert.equal(run.actions[0].disabled, false, 'Activation réessayable');
	run = await scenario({ addon: true, theme: true });
	assert.deepEqual(run.calls.filter(({ action }) => action === 'install').map(({ slug }) => slug), ['elementor', 'elementskit-lite', 'astra']);
	assert.equal(run.submissions.find((body) => body.get('slug') === 'astra').get('type'), 'theme');
	assert.deepEqual(run.submissions[0].getAll('themes[]'), ['astra']);
	assert.deepEqual(run.submissions[0].getAll('plugins[]'), ['elementor', 'elementskit-lite']);
	run = await scenario({ theme: true, onlyTheme: true });
	assert.deepEqual(run.calls.filter(({ action }) => action === 'install').map(({ slug }) => slug), ['astra']);
	assert.equal(run.ids['wsp-result'].textContent, 'done');
	run = await scenario({ theme: true, failed: 'astra', remove: true });
	assert.equal(run.calls.some(({ action }) => action === 'finish'), false);
	assert.equal(run.themes[0].disabled, false, 'Thème en échec réessayable');
	run = await scenario({ addon: true, beforeSubmit: (toggles) => {
		toggles.at(-1).listeners.change();
		assert.equal(toggles[1].checked, true, 'Cocher un addon sélectionne Elementor');
		toggles[1].checked = false;
		toggles[1].listeners.change();
		assert.equal(toggles.at(-1).checked, false, 'Décocher Elementor désélectionne ses addons');
	} });
	assert.equal(run.calls.length, 0);
	run = await scenario({ addon: true, theme: true, beforeSubmit: (toggles, { themes, ids }) => {
		const modulesControl = ids['wsp-select-all-plugins'];
		const themesControl = ids['wsp-select-all-themes'];
		assert.equal(modulesControl.checked, false, 'Sélection partielle : interrupteur global éteint');
		assert.equal(themesControl.checked, true, 'Sélection complète : interrupteur global allumé');
		modulesControl.checked = true;
		modulesControl.listeners.change();
		assert.ok(toggles.filter((input) => !input.disabled).every((input) => input.checked), 'Tous les modules et addons disponibles sont sélectionnés');
		assert.equal(toggles[4].checked, false, 'Module déjà installé ignoré');
		themesControl.checked = false;
		themesControl.listeners.change();
		assert.equal(themes[0].checked, false);
		assert.equal(modulesControl.checked, true, 'Les sections restent indépendantes');
		toggles[0].checked = false;
		toggles[0].listeners.change();
		assert.equal(modulesControl.checked, false, 'Synchronisation après désélection individuelle');
		modulesControl.checked = false;
		modulesControl.listeners.change();
		assert.ok(toggles.every((input) => !input.checked), 'Tout désélectionner inclut les addons');
		themesControl.checked = true;
		themesControl.listeners.change();
		assert.equal(themes[0].checked, true);
		assert.ok(toggles.every((input) => !input.checked));
	} });
	assert.deepEqual(run.calls.filter(({ action }) => action === 'install').map(({ slug }) => slug), ['astra']);
	assert.equal(run.ids['wsp-select-all-themes'].disabled, true, 'Interrupteur désactivé quand aucun thème ne reste à installer');
	console.log('10 parcours JavaScript vérifiés, dont sélection globale indépendante, addons, synchronisation et éléments indisponibles.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
