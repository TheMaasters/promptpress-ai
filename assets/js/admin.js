// AI Page Builder - Admin Utilities Script
(function () {
  'use strict';

  const AIPBAdmin = {
    init() {
      this.initColorPickers();
      this.initMediaUploaders();
      this.initSettingsSave();
      this.initApiKeyToggle();
      this.initTestConnection();
      this.initDeleteConfirmations();
      this.initTabs();
      this.initSearch();
      this.initCopyPrompt();
      this.initExportImport();
      this.initBrandSettings();
      this.initTemplateButtons();
      this.initHistoryActions();
    },

    /* -----------------------------------------------------------
     * Color Pickers
     * ----------------------------------------------------------- */
    initColorPickers() {
      document.querySelectorAll('.aipb-color-field').forEach((field) => {
        const colorInput = field.querySelector('input[type="color"]');
        const hexInput = field.querySelector('.aipb-color-hex');
        const hiddenInput = field.querySelector('input[type="hidden"]');

        if (colorInput && hexInput) {
          colorInput.addEventListener('input', () => {
            hexInput.value = colorInput.value;
            if (hiddenInput) hiddenInput.value = colorInput.value;
          });

          hexInput.addEventListener('input', () => {
            const val = hexInput.value;
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
              colorInput.value = val;
              if (hiddenInput) hiddenInput.value = val;
            }
          });

          hexInput.addEventListener('blur', () => {
            let val = hexInput.value.trim();
            if (!val.startsWith('#')) val = '#' + val;
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
              colorInput.value = val;
              hexInput.value = val;
              if (hiddenInput) hiddenInput.value = val;
            }
          });
        }
      });

      if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
        jQuery('.aipb-wp-color-picker').wpColorPicker({
          change: function (event, ui) {
            const input = jQuery(event.target);
            const name = input.attr('name');
            if (name) {
              input.val(ui.color.toString());
            }
          },
        });
      }
    },

    /* -----------------------------------------------------------
     * Media Uploaders (Logo etc.)
     * ----------------------------------------------------------- */
    initMediaUploaders() {
      document.querySelectorAll('.aipb-media-upload-btn, #aipb-upload-logo').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();

          const targetId = btn.dataset.target || (btn.id === 'aipb-upload-logo' ? 'logo_id' : '');
          const previewId = btn.dataset.preview || (btn.id === 'aipb-upload-logo' ? 'logo-preview' : '');

          if (typeof wp === 'undefined' || !wp.media) {
            this.fallbackFileInput(btn);
            return;
          }

          const frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' },
          });

          frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            const url = attachment.sizes?.medium?.url || attachment.url;

            const input = document.getElementById(targetId);
            if (input) input.value = attachment.id;

            if (previewId) {
              const preview = document.getElementById(previewId);
              if (preview) {
                if (preview.tagName === 'IMG') {
                  preview.src = url;
                } else {
                  preview.innerHTML = `<img src="${url}" alt="Preview" style="max-width:200px;border-radius:8px;">`;
                }
                preview.style.display = 'block';
              }
            }
          });

          frame.open();
        });
      });

      document.querySelectorAll('.aipb-media-remove-btn, #aipb-remove-logo').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const targetId = btn.dataset.target || (btn.id === 'aipb-remove-logo' ? 'logo_id' : '');
          const previewId = btn.dataset.preview || (btn.id === 'aipb-remove-logo' ? 'logo-preview' : '');

          const input = document.getElementById(targetId);
          if (input) input.value = '';

          if (previewId) {
            const preview = document.getElementById(previewId);
            if (preview) {
              preview.innerHTML = '';
              preview.style.display = 'none';
            }
          }
        });
      });
    },

    fallbackFileInput(btn) {
      const targetId = btn.dataset.target;
      const input = document.getElementById(targetId);
      if (input) input.click();
    },

    /* -----------------------------------------------------------
     * Settings Form (AJAX Save)
     * ----------------------------------------------------------- */
    initSettingsSave() {
      const form = document.getElementById('aipb-settings-form');
      if (!form) return;

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveSettings(form);
      });
    },

    async saveSettings(form) {
      const submitBtn = form.querySelector('[type="submit"]');
      const originalText = submitBtn?.innerHTML;

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="aipb-spinner aipb-spinner-dark"></span> Saving...';
      }

      try {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((val, key) => {
          if (key === 'api_key' && val.includes('•')) {
            return;
          }
          data[key] = val;
        });

        data.debug_logging = form.querySelector('[name="debug_logging"]')?.checked ? true : false;

        const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
        const base = (vars.apiUrl || '/wp-json/promptpress-ai/v1').replace(/\/$/, '');
        const res = await fetch(base + '/settings', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': vars.nonce || '',
          },
          credentials: 'same-origin',
          body: JSON.stringify(data),
        });

        const result = await this.parseJsonResponse(res);

        if (result.success) {
          if (result.data?.api_key_masked) {
            const apiKeyField = form.querySelector('[name="api_key"]');
            if (apiKeyField) {
              apiKeyField.value = result.data.api_key_masked;
              apiKeyField.type = 'password';
            }
          }
          this.showToast('Settings saved successfully.', 'success');
        } else {
          this.showToast(result.data?.message || 'Failed to save settings.', 'error');
        }
      } catch (err) {
        this.showToast(err.message || 'Error saving settings.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText || 'Save';
        }
      }
    },

    /* -----------------------------------------------------------
     * API Key Show/Hide Toggle
     * ----------------------------------------------------------- */
    initApiKeyToggle() {
      document.querySelectorAll('.aipb-toggle-key').forEach((btn) => {
        btn.addEventListener('click', () => {
          const input = btn.closest('.aipb-input-group')?.querySelector('input[name="api_key"]');
          if (!input) return;

          const isPassword = input.type === 'password';
          input.type = isPassword ? 'text' : 'password';
          btn.innerHTML = isPassword ? '&#128065;' : '&#128064;';
          btn.setAttribute('aria-label', isPassword ? 'Hide API key' : 'Show API key');
        });
      });
    },

    /* -----------------------------------------------------------
     * Test Connection
     * ----------------------------------------------------------- */
    initTestConnection() {
      const btn = document.getElementById('aipb-test-connection');
      if (!btn) return;

      btn.addEventListener('click', () => this.testConnection(btn));
    },

    async testConnection(btn) {
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="aipb-spinner aipb-spinner-dark" style="width:14px;height:14px;border-width:2px;"></span> Testing...';

      const statusEl = document.getElementById('aipb-connection-status');

      try {
        const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
        const base = (vars.apiUrl || '/wp-json/promptpress-ai/v1').replace(/\/$/, '');
        const form = document.getElementById('aipb-settings-form');
        const apiKeyField = form?.querySelector('[name="api_key"]');
        const providerField = form?.querySelector('[name="provider"]');
        const modelField = form?.querySelector('[name="model"]');

        const payload = {
          provider: providerField?.value || '',
          model: modelField?.value || '',
          api_key: apiKeyField?.value || '',
        };

        const res = await fetch(base + '/test-connection', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': vars.nonce || '',
          },
          credentials: 'same-origin',
          body: JSON.stringify(payload),
        });

        const result = await this.parseJsonResponse(res);

        if (statusEl) {
          if (result.success) {
            statusEl.className = 'aipb-connection-status aipb-connection-success';
            statusEl.textContent = result.data?.message || 'Connection successful!';
          } else {
            statusEl.className = 'aipb-connection-status aipb-connection-error';
            statusEl.textContent = result.data?.message || 'Connection failed.';
          }
          statusEl.style.display = 'block';
        }

        this.showToast(result.data?.message || (result.success ? 'Connection successful!' : 'Connection failed.'), result.success ? 'success' : 'error');
      } catch (err) {
        if (statusEl) {
          statusEl.className = 'aipb-connection-status aipb-connection-error';
          statusEl.textContent = err.message || 'Connection error.';
          statusEl.style.display = 'block';
        }
        this.showToast(err.message || 'Connection error.', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    },

    /* -----------------------------------------------------------
     * Delete Confirmations
     * ----------------------------------------------------------- */
    initDeleteConfirmations() {
      document.querySelectorAll('[data-aipb-confirm]').forEach((el) => {
        el.addEventListener('click', (e) => {
          const message = el.dataset.aipbConfirm || 'Are you sure?';
          if (!confirm(message)) {
            e.preventDefault();
            e.stopImmediatePropagation();
          }
        });
      });

      document.querySelectorAll('.aipb-delete-history-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          if (!confirm('Delete this item permanently? This cannot be undone.')) return;

          const id = btn.dataset.id;
          if (!id) return;

          this.deleteHistoryItem(id, btn);
        });
      });
    },

    async deleteHistoryItem(id, btn) {
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="aipb-spinner aipb-spinner-dark" style="width:14px;height:14px;border-width:2px;"></span>';

      try {
        const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
        const res = await fetch(`${vars.apiUrl || '/wp-json/promptpress-ai/v1'}/history/${id}`, {
          method: 'DELETE',
          headers: { 'X-WP-Nonce': vars.nonce || '' },
          credentials: 'same-origin',
        });

        const result = await res.json();

        if (result.success) {
          const row = btn.closest('tr');
          if (row) {
            row.style.transition = 'opacity 0.3s, transform 0.3s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            setTimeout(() => row.remove(), 300);
          }
          this.showToast('Item deleted.', 'success');
        } else {
          this.showToast(result.data?.message || 'Delete failed.', 'error');
          btn.innerHTML = originalText;
          btn.disabled = false;
        }
      } catch (err) {
        this.showToast(err.message || 'Delete error.', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
      }
    },

    /* -----------------------------------------------------------
     * Tabs
     * ----------------------------------------------------------- */
    initTabs() {
      document.querySelectorAll('.aipb-tabs').forEach((tabBar) => {
        const tabs = tabBar.querySelectorAll('.aipb-tab');
        const containerId = tabBar.dataset.tabContainer;
        const container = containerId
          ? document.getElementById(containerId)
          : tabBar.closest('.aipb-admin');

        if (!container) return;

        tabs.forEach((tab) => {
          tab.addEventListener('click', (e) => {
            e.preventDefault();
            const target = tab.dataset.tab;
            if (!target) return;

            tabs.forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');

            container.querySelectorAll('.aipb-tab-content').forEach((content) => {
              content.classList.toggle('active', content.dataset.tabContent === target);
            });
          });
        });
      });
    },

    /* -----------------------------------------------------------
     * Search / Filter
     * ----------------------------------------------------------- */
    initSearch() {
      document.querySelectorAll('.aipb-search-bar input').forEach((input) => {
        let debounceTimer;

        input.addEventListener('input', () => {
          clearTimeout(debounceTimer);
          debounceTimer = setTimeout(() => {
            const query = input.value.toLowerCase().trim();
            const targetSelector = input.dataset.aipbSearchTarget;
            if (!targetSelector) return;

            document.querySelectorAll(targetSelector).forEach((item) => {
              const text = (item.textContent || '').toLowerCase();
              const match = !query || text.includes(query);
              item.style.display = match ? '' : 'none';
            });
          }, 250);
        });
      });
    },

    /* -----------------------------------------------------------
     * Copy Prompt to Clipboard
     * ----------------------------------------------------------- */
    initCopyPrompt() {
      document.querySelectorAll('.aipb-copy-prompt-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const text = btn.dataset.prompt || btn.closest('.aipb-table-row')?.querySelector('.aipb-prompt-col')?.textContent || '';
          if (!text) return;

          if (navigator.clipboard) {
            navigator.clipboard.writeText(text.trim()).then(() => {
              this.showToast('Prompt copied to clipboard.', 'success');
            });
          } else {
            const ta = document.createElement('textarea');
            ta.value = text.trim();
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            ta.remove();
            this.showToast('Prompt copied to clipboard.', 'success');
          }
        });
      });
    },

    /* -----------------------------------------------------------
     * Export / Import Settings
     * ----------------------------------------------------------- */
    initExportImport() {
      const exportBtn = document.getElementById('aipb-export-settings');
      if (exportBtn) {
        exportBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.exportSettings();
        });
      }

      const importBtn = document.getElementById('aipb-import-btn');
      if (importBtn) {
        importBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.importSettings();
        });
      }
    },

    exportSettings() {
      const form = document.getElementById('aipb-settings-form');
      if (!form) return;

      const formData = new FormData(form);
      const data = {};
      formData.forEach((val, key) => {
        data[key] = val;
      });

      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'promptpress-settings.json';
      a.click();
      URL.revokeObjectURL(url);

      this.showToast('Settings exported.', 'success');
    },

    importSettings() {
      const input = document.getElementById('aipb-import-file');
      if (!input || !input.files.length) {
        this.showToast('Select a JSON file to import.', 'warning');
        return;
      }

      const file = input.files[0];
      const reader = new FileReader();

      reader.onload = (e) => {
        try {
          const data = JSON.parse(e.target.result);
          const form = document.getElementById('aipb-settings-form');
          if (!form) return;

          Object.entries(data).forEach(([key, val]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
              field.value = val;
              if (field.type === 'color') {
                const colorField = field.closest('.aipb-color-field');
                if (colorField) {
                  const hex = colorField.querySelector('.aipb-color-hex');
                  if (hex) hex.value = val;
                }
              }
            }
          });

          this.showToast('Settings imported. Click Save to apply.', 'info');
        } catch {
          this.showToast('Invalid JSON file.', 'error');
        }
      };

      reader.readAsText(file);
    },

    /* -----------------------------------------------------------
     * Brand Settings
     * ----------------------------------------------------------- */
    initBrandSettings() {
      const form = document.getElementById('aipb-brand-form');
      if (!form) return;

      form.addEventListener('submit', (e) => {
        e.preventDefault();
        this.saveBrandSettings(form);
      });

      const removeBtn = document.getElementById('aipb-remove-logo');
      if (removeBtn) {
        removeBtn.addEventListener('click', () => {
          const input = document.getElementById('logo_id');
          const preview = document.getElementById('logo-preview');
          if (input) input.value = '';
          if (preview) preview.innerHTML = '<div class="aipb-logo-placeholder"><span class="dashicons dashicons-format-image"></span><p>No logo uploaded</p></div>';
          removeBtn.style.display = 'none';
        });
      }
    },

    async saveBrandSettings(form) {
      const submitBtn = form.querySelector('[type="submit"]');
      const originalText = submitBtn?.innerHTML;
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="aipb-spinner aipb-spinner-dark"></span> Saving...';
      }

      try {
        const data = {};
        new FormData(form).forEach((value, key) => {
          if (key !== 'aipb_brand_nonce') data[key] = value;
        });
        const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
        const base = (vars.apiUrl || '/wp-json/promptpress-ai/v1').replace(/\/$/, '');
        const res = await fetch(base + '/brand-settings', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': vars.nonce || '' },
          credentials: 'same-origin',
          body: JSON.stringify(data),
        });
        const result = await this.parseJsonResponse(res);
        if (!result.success) throw new Error(result.data?.message || 'Failed to save brand settings.');
        this.showToast('Brand settings saved successfully.', 'success');
      } catch (err) {
        this.showToast(err.message || 'Error saving brand settings.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalText || 'Save Brand Settings';
        }
      }
    },

    /* -----------------------------------------------------------
     * Template Buttons
     * ----------------------------------------------------------- */
    initTemplateButtons() {
      const openBuilder = (prompt) => {
        if (!prompt) return;
        const url = new URL(window.location.href);
        url.searchParams.set('page', 'aipb-builder');
        url.searchParams.set('prompt', prompt);
        window.location.href = url.toString();
      };

      document.querySelectorAll('.aipb-use-template-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          openBuilder(btn.dataset.prompt || '');
        });
      });

      const modal = document.getElementById('aipb-template-modal');
      if (!modal) return;

      const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('aipb-modal-open');
      };

      document.querySelectorAll('.aipb-template-preview-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const title = document.getElementById('aipb-template-modal-title');
          const category = document.getElementById('aipb-template-modal-category');
          const description = document.getElementById('aipb-template-modal-description');
          const prompt = document.getElementById('aipb-template-modal-prompt');
          const useBtn = document.getElementById('aipb-modal-use-template');

          if (title) title.textContent = btn.dataset.name || '';
          if (category) category.textContent = btn.dataset.category || '';
          if (description) description.textContent = btn.dataset.description || '';
          if (prompt) prompt.textContent = btn.dataset.prompt || '';
          if (useBtn) useBtn.dataset.prompt = btn.dataset.prompt || '';

          modal.hidden = false;
          document.body.classList.add('aipb-modal-open');
        });
      });

      modal.querySelectorAll('[data-template-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
      });

      document.getElementById('aipb-modal-use-template')?.addEventListener('click', (e) => {
        openBuilder(e.currentTarget.dataset.prompt || '');
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
      });
    },

    /* -----------------------------------------------------------
     * History Actions
     * ----------------------------------------------------------- */
    initHistoryActions() {
      document.querySelectorAll('.aipb-delete-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
          if (!window.confirm('Are you sure you want to delete this history item?')) return;
          try {
            const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
            const base = (vars.apiUrl || '/wp-json/promptpress-ai/v1').replace(/\/$/, '');
            const res = await fetch(base + '/history/' + encodeURIComponent(btn.dataset.id), {
              method: 'DELETE',
              headers: { 'X-WP-Nonce': vars.nonce || '' },
              credentials: 'same-origin',
            });
            const result = await this.parseJsonResponse(res);
            if (!result.success) throw new Error(result.data?.message || 'Unable to delete history.');
            btn.closest('tr')?.remove();
            this.showToast('History item deleted.', 'success');
          } catch (err) {
            this.showToast(err.message || 'Unable to delete history.', 'error');
          }
        });
      });

      document.querySelectorAll('.aipb-regenerate-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const prompt = btn.dataset.prompt || '';
          if (!prompt) return;
          const url = new URL(window.location.href);
          url.searchParams.set('page', 'aipb-builder');
          url.searchParams.set('prompt', prompt);
          window.location.href = url.toString();
        });
      });
    },

    /* -----------------------------------------------------------
     * Toast Helper
     * ----------------------------------------------------------- */
    showToast(message, type = 'info') {
      let container = document.getElementById('aipb-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'aipb-toast-container';
        container.className = 'aipb-toast-container';
        document.body.appendChild(container);
      }

      const toast = document.createElement('div');
      toast.className = `aipb-toast toast-${type}`;
      const icons = { success: '&#10003;', error: '&#10007;', warning: '&#9888;', info: '&#8505;' };
      toast.innerHTML = `<span>${icons[type] || icons.info}</span><span>${this.escapeHtml(message)}</span>`;
      container.appendChild(toast);

      setTimeout(() => {
        toast.classList.add('removing');
        toast.addEventListener('animationend', () => toast.remove());
      }, 4000);
    },

    async parseJsonResponse(response) {
      const text = await response.text();

      let data = null;
      try {
        data = text ? JSON.parse(text) : null;
      } catch (e) {
        const snippet = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 240);
        throw new Error(
          `WordPress returned an invalid response (HTTP ${response.status}).${snippet ? ` ${snippet}` : ''}`,
        );
      }

      if (!response.ok) {
        const message =
          data?.message ||
          data?.data?.message ||
          `Request failed with HTTP ${response.status}.`;
        throw new Error(message);
      }

      return data || {};
    },

    escapeHtml(str) {
      if (!str) return '';
      const div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    },
  };

  document.addEventListener('DOMContentLoaded', () => AIPBAdmin.init());
})();
