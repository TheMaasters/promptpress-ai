// PromptPress AI - Elementor editor integration.
// The actual PromptPress AI widget is registered through Elementor's
// widget manager. This file only exposes the REST configuration to the
// Elementor editor and intentionally avoids private/legacy panel APIs.
(function () {
  'use strict';

  function ready() {
    if (typeof window.aipbElementor === 'undefined') {
      window.aipbElementor = {};
    }

    window.aipbElementor.apiUrl =
      window.aipbElementor.apiUrl ||
      (window.wpApiSettings && window.wpApiSettings.root
        ? window.wpApiSettings.root.replace(/\/$/, '') + '/promptpress-ai/v1'
        : '');

    window.aipbElementor.nonce =
      window.aipbElementor.nonce ||
      (window.wpApiSettings && window.wpApiSettings.nonce
        ? window.wpApiSettings.nonce
        : '');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ready);
  } else {
    ready();
  }
})();
