/* global wpStarterPack */
(() => {
	'use strict';
	const pending = [...document.querySelectorAll('.wsp-metadata')];
	const config = wpStarterPack;
	const load = async (element) => {
		try {
			const body = new URLSearchParams({ action: 'wp_starter_pack_metadata', nonce: config.metadataNonce, type: element.dataset.type, slug: element.dataset.slug });
			const response = await fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body });
			const payload = await response.json();
			if (!response.ok || !payload.success) throw new Error('metadata');
			['rating', 'version', 'activeInstalls'].forEach((field) => {
				element.querySelector(`[data-field="${field}"]`).textContent = payload.data[field] || '—';
			});
			if (payload.data.image) {
				const image = element.closest('.wsp-module').querySelector('.wsp-resource-image');
				image.hidden = false;
				image.nextElementSibling.hidden = true;
				image.src = payload.data.image;
			}
		} catch (error) {
			element.querySelectorAll('dd').forEach((field) => { field.textContent = '—'; });
			const notice = element.nextElementSibling;
			notice.textContent = config.messages.metadataUnavailable;
			notice.hidden = false;
		} finally {
			element.removeAttribute('aria-busy');
		}
	};
	// Limiter la charge serveur et les connexions au répertoire à trois requêtes simultanées.
	const worker = async () => {
		while (pending.length) await load(pending.shift());
	};
	Promise.all([worker(), worker(), worker()]);
})();
