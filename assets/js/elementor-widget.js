// PromptPress AI - Elementor widget assistant.
(function () {
  'use strict';

  function getConfig() {
    const parentConfig =
      window.parent && window.parent !== window
        ? window.parent.aipbElementor
        : window.aipbElementor;

    return parentConfig || {};
  }

  async function parseResponse(response) {
    const text = await response.text();
    let data = {};

    try {
      data = text ? JSON.parse(text) : {};
    } catch (e) {
      throw new Error(
        `WordPress returned an invalid response (HTTP ${response.status}).`,
      );
    }

    if (!response.ok) {
      throw new Error(
        data?.message ||
          data?.data?.message ||
          `Request failed with HTTP ${response.status}.`,
      );
    }

    return data;
  }

  function initWidget(widget) {
    if (widget.dataset.aipbReady === '1') return;
    widget.dataset.aipbReady = '1';

    const button = widget.querySelector('.aipb-elementor-widget__generate');
    const promptField = widget.querySelector('.aipb-elementor-widget__prompt');
    const status = widget.querySelector('.aipb-elementor-widget__status');

    if (!button || !promptField || !status) return;

    button.addEventListener('click', async function () {
      const prompt = promptField.value.trim();

      if (!prompt) {
        status.textContent = 'Please describe what you want to create.';
        return;
      }

      const config = getConfig();
      const baseUrl = (config.apiUrl || '').replace(/\/$/, '');

      if (!baseUrl) {
        status.textContent =
          'PromptPress AI REST configuration is unavailable. Refresh Elementor.';
        return;
      }

      button.disabled = true;
      status.textContent = 'Generating your Elementor draft…';

      try {
        const response = await fetch(baseUrl + '/generate', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': config.nonce || '',
          },
          body: JSON.stringify({
            prompt: prompt,
            content_type: 'page',
            editor: 'elementor',
            content_only: false,
            publish_status: 'draft',
          }),
        });

        const result = await parseResponse(response);

        if (!result.success) {
          throw new Error(
            result.data?.message || 'AI generation failed.',
          );
        }

        const postId = result.data?.post_id || 0;
        status.textContent =
          'Elementor draft created successfully. Opening the editor…';

        if (postId) {
          const targetWindow =
            window.parent && window.parent !== window ? window.parent : window;
          const adminBase = targetWindow.location.origin + '/wp-admin/post.php';
          const editUrl =
            adminBase + '?post=' + encodeURIComponent(postId) + '&action=elementor';
          targetWindow.location.href = editUrl;
        } else {
          status.textContent = 'Draft was created but no post ID was returned.';
        }
      } catch (error) {
        console.error('PromptPress AI Elementor widget error:', error);
        status.textContent =
          error?.message || 'Could not generate the Elementor draft.';
      } finally {
        button.disabled = false;
      }
    });
  }

  function scan() {
    document
      .querySelectorAll('.aipb-elementor-widget')
      .forEach(initWidget);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }

  if (window.elementorFrontend) {
    window.elementorFrontend.hooks.addAction(
      'frontend/element_ready/promptpress_ai.default',
      scan,
    );
  }
})();
