/* global wpStarterPack */
(() => {
	'use strict';
	const form = document.getElementById('wsp-installation-form');
	if (!form) return;
	const config = wpStarterPack;
	const messages = config.messages;
	const pluginToggles = [...form.querySelectorAll('input[name="plugins[]"]')];
	const themeToggles = [...form.querySelectorAll('input[name="themes[]"]')];
	const toggles = [...pluginToggles, ...themeToggles];
	const sectionSelections = [
		{ control: document.getElementById('wsp-select-all-plugins'), items: pluginToggles },
		{ control: document.getElementById('wsp-select-all-themes'), items: themeToggles },
	];
	const configureButtons = [...form.querySelectorAll('.wsp-configure-button')];
	const button = document.getElementById('wsp-install-button');
	const count = document.getElementById('wsp-selection-count');
	const cleanup = document.getElementById('wsp-remove-after-install');
	const result = document.getElementById('wsp-result');
	const progress = document.getElementById('wsp-progress');
	const bar = document.getElementById('wsp-progress-bar');
	const extensionsLink = document.getElementById('wsp-extensions-link');
	const initialButtonText = button.textContent;
	let busy = false;
	let removed = false;

	const selection = () => toggles.filter((input) => input.checked && !input.disabled);
	const updateConfigureButtons = () => configureButtons.forEach((action) => {
		action.hidden = removed || action.dataset.installed !== 'true' || action.dataset.allowed !== 'true';
		action.disabled = busy || action.hidden;
	});
	const updateSelection = () => {
		count.textContent = `${selection().length} / ${toggles.length}`;
		button.disabled = busy || removed || selection().length === 0;
		sectionSelections.forEach(({ control, items }) => {
			if (!control) return;
			if (busy) {
				control.disabled = true;
				return;
			}
			const available = items.filter((input) => !input.disabled);
			control.disabled = removed || available.length === 0;
			control.checked = available.length > 0 && available.every((input) => input.checked);
		});
	};
	const showResult = (text, error = false) => {
		result.textContent = text;
		result.classList.toggle('wsp-result-error', error);
		result.hidden = false;
	};
	const request = async (action, fields = {}) => {
		const body = new URLSearchParams({ action: `wp_starter_pack_${action}`, nonce: action === 'activate' ? config.activationNonce : config.nonce });
		Object.entries(fields).forEach(([key, value]) => {
			if (Array.isArray(value)) value.forEach((item) => body.append(`${key}[]`, item));
			else body.append(key, value);
		});
		let response;
		let payload;
		try {
			response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body });
			payload = await response.json();
		} catch (error) {
			throw new Error(messages.networkError);
		}
		if (!response.ok || !payload.success) {
			throw new Error(payload.data?.message || messages.networkError);
		}
		return payload.data;
	};

	form.querySelectorAll('.wsp-icon img').forEach((image) => {
		const fallback = () => {
			image.hidden = true;
			image.nextElementSibling.hidden = false;
		};
		image.addEventListener('error', fallback);
		if (image.complete && image.naturalWidth === 0) fallback();
	});
	const selectDependencies = () => pluginToggles.filter((input) => input.checked && input.dataset.dependency).forEach((input) => {
		const parent = pluginToggles.find((candidate) => candidate.value === input.dataset.dependency);
		if (parent && !parent.disabled) parent.checked = true;
	});
	sectionSelections.forEach(({ control, items }) => {
		if (!control) return;
		control.addEventListener('change', () => {
			if (busy || removed || control.disabled) return;
			items.filter((input) => !input.disabled).forEach((input) => { input.checked = control.checked; });
			if (items === pluginToggles) selectDependencies();
			updateSelection();
		});
	});
	toggles.forEach((input) => input.addEventListener('change', () => {
		if (!input.checked && !input.disabled) {
			pluginToggles.filter((child) => child.dataset.dependency === input.value).forEach((child) => { child.checked = false; });
		}
		selectDependencies();
		updateSelection();
	}));
	configureButtons.forEach((action) => action.addEventListener('click', async () => {
		if (busy || removed || action.disabled || action.hidden) return;
		busy = true;
		form.setAttribute('aria-busy', 'true');
		updateSelection();
		updateConfigureButtons();
		showResult(messages.activating);
		try {
			const outcome = await request('activate', { slug: action.dataset.slug });
			window.location.assign(outcome.url);
		} catch (error) {
			showResult(error.message, true);
		} finally {
			busy = false;
			form.removeAttribute('aria-busy');
			updateSelection();
			updateConfigureButtons();
		}
	}));
	window.addEventListener('beforeunload', (event) => {
		if (busy) {
			event.preventDefault();
			event.returnValue = '';
		}
	});

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (busy || removed) return;
		selectDependencies();
		const chosen = selection();
		if (!chosen.length) {
			showResult(messages.choose, true);
			return;
		}
		const cleanupWasDisabled = cleanup.disabled;
		const previouslyDisabled = new Set(toggles.filter((input) => input.disabled));
		const removeAfterInstall = cleanup.checked;
		busy = true;
		updateSelection();
		updateConfigureButtons();
		form.setAttribute('aria-busy', 'true');
		toggles.forEach((input) => { input.disabled = true; });
		cleanup.disabled = true;
		button.disabled = true;
		button.textContent = messages.installing;
		progress.hidden = false;
		bar.max = chosen.length;
		bar.value = 0;
		showResult(messages.installing);
		try {
			const { token } = await request('start', {
				plugins: chosen.filter((input) => pluginToggles.includes(input)).map((input) => input.value),
				themes: chosen.filter((input) => themeToggles.includes(input)).map((input) => input.value),
				remove: removeAfterInstall ? '1' : '0',
			});
			let failed = false;
			for (const input of chosen) {
				const row = input.closest('.wsp-module');
				const status = row.querySelector('.wsp-module-status');
				status.textContent = messages.installing;
				row.classList.remove('wsp-module-error');
				try {
					await request('install', { token, slug: input.value, type: input.dataset.type || 'plugin' });
					status.textContent = messages.installed;
					input.checked = false;
					input.dataset.installed = 'true';
					const action = pluginToggles.includes(input) && configureButtons.find((candidate) => candidate.dataset.slug === input.value);
					if (action) action.dataset.installed = 'true';
				} catch (error) {
					failed = true;
					status.textContent = `${messages.failed} : ${error.message}`;
					row.classList.add('wsp-module-error');
				}
				bar.value += 1;
			}
			if (failed) {
				showResult(messages.partial, true);
			} else {
				showResult(messages.finalizing);
				const outcome = await request('finish', { token });
				removed = outcome.deleted;
				showResult(removed ? messages.deleted : messages.done);
			}
		} catch (error) {
			showResult(error.message, true);
		} finally {
			busy = false;
			form.removeAttribute('aria-busy');
			// Les modules déjà présents avant l’opération restent non sélectionnables.
			toggles.forEach((input) => {
				input.disabled = removed || input.dataset.installed === 'true' || previouslyDisabled.has(input);
			});
			cleanup.disabled = removed || cleanupWasDisabled;
			button.textContent = initialButtonText;
			extensionsLink.hidden = false;
			updateSelection();
			updateConfigureButtons();
		}
	});
	updateSelection();
	updateConfigureButtons();
})();
