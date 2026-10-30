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

async function scenario({ failed = '', remove = false, startFailure = false } = {}) {
	const ids = {};
	for (const id of ['wsp-installation-form', 'wsp-install-button', 'wsp-selection-count', 'wsp-remove-after-install', 'wsp-result', 'wsp-progress', 'wsp-progress-bar', 'wsp-extensions-link']) ids[id] = element();
	const toggles = ['wordfence', 'elementor', 'wordpress-seo', 'wpforms-lite', 'google-site-kit'].map((slug, index) => {
		const input = element();
		input.value = slug;
		input.checked = index < 2;
		input.disabled = index === 4;
		const status = element();
		const row = element();
		row.querySelector = () => status;
		input.closest = () => row;
		input.status = status;
		return input;
	});
	ids['wsp-installation-form'].querySelectorAll = (selector) => selector.includes('plugins') ? toggles : [];
	ids['wsp-remove-after-install'].checked = remove;
	const calls = [];
	const messages = Object.fromEntries(['networkError', 'choose', 'installing', 'installed', 'failed', 'partial', 'done', 'deleted', 'finalizing'].map((key) => [key, key]));
	vm.runInNewContext(source, {
		document: { getElementById: (id) => ids[id] },
		window: { addEventListener() {} }, URLSearchParams,
		wpStarterPack: { ajaxUrl: '/ajax', nonce: 'nonce', messages },
		fetch: async (url, options) => {
			const action = options.body.get('action').replace('wp_starter_pack_', '');
			const slug = options.body.get('slug');
			calls.push({ action, slug });
			const fail = (action === 'install' && slug === failed) || (action === 'start' && startFailure);
			return { ok: !fail, json: async () => ({ success: !fail, data: fail ? { message: 'test failure' } : action === 'start' ? { token: 'session' } : action === 'finish' ? { deleted: remove } : { installed: true } }) };
		},
	});
	await ids['wsp-installation-form'].listeners.submit({ preventDefault() {} });
	return { ids, toggles, calls };
}

(async () => {
	let run = await scenario();
	assert.deepEqual(run.calls.map(({ action }) => action), ['start', 'install', 'install', 'finish']);
	assert.equal(run.toggles[0].disabled, true);
	assert.equal(run.toggles[2].disabled, false, 'Un module non sélectionné reste disponible après l’opération');
	assert.equal(run.toggles[4].disabled, true, 'Un module déjà installé reste indisponible');
	assert.equal(run.ids['wsp-result'].textContent, 'done');
	run = await scenario({ failed: 'wordfence', remove: true });
	assert.equal(run.calls.some(({ action }) => action === 'finish'), false, 'Aucune finalisation en cas d’échec');
	assert.equal(run.toggles[0].disabled, false);
	assert.equal(run.toggles[0].checked, true, 'Le module en échec reste sélectionné pour réessayer');
	assert.equal(run.toggles[1].disabled, true);
	assert.equal(run.ids['wsp-result'].textContent, 'partial');
	run = await scenario({ remove: true });
	assert.equal(run.ids['wsp-result'].textContent, 'deleted');
	assert.equal(run.ids['wsp-install-button'].disabled, true);
	assert.ok(run.toggles.every((input) => input.disabled));
	run = await scenario({ startFailure: true });
	assert.equal(run.calls.length, 1);
	assert.equal(run.toggles[0].disabled, false);
	assert.equal(run.ids['wsp-install-button'].disabled, false);
	assert.equal(run.ids['wsp-result'].textContent, 'test failure');
	console.log('4 parcours JavaScript vérifiés : réussite, échec partiel, suppression, échec initial.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
