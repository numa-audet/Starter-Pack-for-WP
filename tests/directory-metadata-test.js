'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const { setImmediate: nextTick } = require('node:timers/promises');

(async () => {
	let inFlight = 0;
	let maximum = 0;
	let calls = 0;
	const cards = Array.from({ length: 15 }, (_, index) => {
		const fields = Object.fromEntries(['rating', 'version', 'activeInstalls'].map((key) => [key, { textContent: '…' }]));
		const image = { hidden: true, nextElementSibling: { hidden: false } };
		return {
			dataset: { type: index < 11 ? 'plugin' : 'theme', slug: `item-${index}` },
			fields, image, busy: true, nextElementSibling: { hidden: true },
			querySelector(selector) { return fields[selector.match(/"([^"]+)"/)[1]]; },
			querySelectorAll() { return Object.values(fields); },
			closest() { return { querySelector: () => image }; },
			removeAttribute() { this.busy = false; },
		};
	});
	vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../assets/js/directory-metadata.js'), 'utf8'), {
		document: { querySelectorAll: () => cards }, URLSearchParams,
		wpStarterPack: { ajaxUrl: '/ajax', metadataNonce: 'metadata-nonce', messages: { metadataUnavailable: 'indisponible' } },
		fetch: async (url, { body }) => {
			assert.equal(body.get('nonce'), 'metadata-nonce');
			++calls;
			++inFlight;
			maximum = Math.max(maximum, inFlight);
			await nextTick();
			--inFlight;
			if (body.get('slug') === 'item-2') throw new Error('Répertoire indisponible');
			return { ok: true, json: async () => ({ success: true, data: { rating: '4,8 / 5', version: '1.2.3', activeInstalls: '10 000+', image: 'https://example.test/icon.png' } }) };
		},
	});
	for (let tick = 0; tick < 30 && cards.some((card) => card.busy); ++tick) await nextTick();
	assert.equal(calls, 15);
	assert.equal(maximum, 3, 'Au maximum trois requêtes simultanées');
	assert.ok(cards.every((card) => !card.busy));
	assert.equal(cards[0].fields.rating.textContent, '4,8 / 5');
	assert.equal(cards[0].image.src, 'https://example.test/icon.png');
	assert.equal(cards[2].fields.rating.textContent, '—');
	assert.equal(cards[2].nextElementSibling.hidden, false);
	assert.equal(cards[2].nextElementSibling.textContent, 'indisponible');
	console.log('Métadonnées vérifiées : affichage, images, panne du répertoire et limite de concurrence.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
