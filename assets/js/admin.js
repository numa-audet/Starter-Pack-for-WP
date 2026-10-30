/* global wpStarterPack */
(() => {
	'use strict';
	const form = document.getElementById('wsp-installation-form');
	if (!form) return;
	const config = wpStarterPack;
	const messages = config.messages;
	const toggles = [...form.querySelectorAll('input[name="plugins[]"]')];
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
	const updateSelection = () => {
		count.textContent = `${selection().length} / ${toggles.length}`;
		button.disabled = busy || removed || selection().length === 0;
	};
	const showResult = (text, error = false) => {
		result.textContent = text;
		result.classList.toggle('wsp-result-error', error);
		result.hidden = false;
	};
	const request = async (action, fields = {}) => {
		const body = new URLSearchParams({ action: `wp_starter_pack_${action}`, nonce: config.nonce });
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
	toggles.forEach((input) => input.addEventListener('change', updateSelection));
	window.addEventListener('beforeunload', (event) => {
		if (busy) {
			event.preventDefault();
			event.returnValue = '';
		}
	});

	form.addEventListener('submit', async (event) => {
		event.preventDefault();
		if (busy || removed) return;
		const chosen = selection();
		if (!chosen.length) {
			showResult(messages.choose, true);
			return;
		}
		const cleanupWasDisabled = cleanup.disabled;
		const previouslyDisabled = new Set(toggles.filter((input) => input.disabled));
		const removeAfterInstall = cleanup.checked;
		busy = true;
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
			const { token } = await request('start', { plugins: chosen.map((input) => input.value), remove: removeAfterInstall ? '1' : '0' });
			let failed = false;
			for (const input of chosen) {
				const row = input.closest('.wsp-module');
				const status = row.querySelector('.wsp-module-status');
				status.textContent = messages.installing;
				row.classList.remove('wsp-module-error');
				try {
					await request('install', { token, slug: input.value });
					status.textContent = messages.installed;
					input.checked = false;
					input.dataset.installed = 'true';
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
		}
	});
	updateSelection();
})();
