// AI Page Builder - Main Builder Script
(function () {
  'use strict';

  const BuilderApp = {
    elements: {},
    state: {
      generating: false,
      generatedPostId: null,
      previewVisible: false,
      currentStep: 0,
    },

    steps: [
      { key: 'understanding', label: 'Understanding', icon: '🧠' },
      { key: 'planning', label: 'Planning', icon: '📋' },
      { key: 'writing', label: 'Writing', icon: '✍️' },
      { key: 'images', label: 'Images', icon: '🖼️' },
      { key: 'building', label: 'Building', icon: '🏗️' },
      { key: 'seo', label: 'SEO', icon: '🔍' },
    ],

    init() {
      this.cacheElements();
      const promptField = document.getElementById('aipb-prompt');
      if (promptField && promptField.dataset.initialPrompt) promptField.value = promptField.dataset.initialPrompt;
      this.bindEvents();
    },

    cacheElements() {
      const ids = [
        'aipb-prompt', 'aipb-generate-btn', 'aipb-content-type', 'aipb-page-type',
        'aipb-tone', 'aipb-page-length', 'aipb-seo-optimize',
        'aipb-progress-container', 'aipb-progress-bar',
        'aipb-steps-container', 'aipb-preview-container',
        'aipb-preview-frame', 'aipb-preview-inner',
        'aipb-action-buttons', 'aipb-builder-form',
      ];
      ids.forEach((id) => {
        this.elements[id.replace(/^aipb-/, '').replace(/-/g, '_')] = document.getElementById(id);
      });
    },

    bindEvents() {
      const generateBtn = document.getElementById('aipb-generate-btn');
      if (generateBtn) {
        generateBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.generate();
        });
      }

      document.querySelectorAll('.aipb-prompt-suggestion').forEach((el) => {
        el.addEventListener('click', (e) => {
          e.preventDefault();
          const textarea = document.getElementById('aipb-prompt');
          if (textarea) {
            textarea.value = el.dataset.prompt || el.textContent.trim();
            textarea.focus();
          }
        });
      });

      document.querySelectorAll('.aipb-device-btn').forEach((btn) => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          this.switchDevice(btn.dataset.device);
        });
      });

      const createDraftBtn = document.getElementById('aipb-create-draft');
      if (createDraftBtn) {
        createDraftBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.createDraft();
        });
      }

      const publishBtn = document.getElementById('aipb-publish-page');
      if (publishBtn) {
        publishBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.publishPage();
        });
      }

      const gutenbergBtn = document.getElementById('aipb-edit-gutenberg');
      if (gutenbergBtn) {
        gutenbergBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.editInGutenberg();
        });
      }

      const elementorBtn = document.getElementById('aipb-edit-elementor');
      if (elementorBtn) {
        elementorBtn.addEventListener('click', (e) => {
          e.preventDefault();
          this.editInElementor();
        });
      }
    },

    getFormData() {
      return {
        prompt: (document.getElementById('aipb-prompt')?.value || '').trim(),
        content_type: document.getElementById('aipb-content-type')?.value || document.getElementById('aipb-page-type')?.value || document.getElementById('aipb-generate-btn')?.dataset.contentType || 'page',
        editor: document.getElementById('aipb-editor')?.value || 'gutenberg',
        language: document.getElementById('aipb-language')?.value || 'en',
        tone: document.getElementById('aipb-tone')?.value || 'professional',
        design_style: document.getElementById('aipb-design-style')?.value || 'modern',
        image_source: document.getElementById('aipb-image-source')?.value || 'ai',
        seo_optimize: document.getElementById('aipb-seo-optimize')?.checked || false,
        publish_status: document.getElementById('aipb-publish-status')?.value || 'draft',
      };
    },

    validate(data) {
      if (!data.prompt) {
        this.showToast('Please enter a prompt describing the page you want to create.', 'warning');
        document.getElementById('aipb-prompt')?.focus();
        return false;
      }
      if (data.prompt.length < 10) {
        this.showToast('Prompt is too short. Please provide more detail.', 'warning');
        document.getElementById('aipb-prompt')?.focus();
        return false;
      }
      return true;
    },

    async generate() {
      if (this.state.generating) return;

      const data = this.getFormData();
      if (!this.validate(data)) return;

      this.state.generating = true;
      this.setButtonLoading(true);
      this.showProgress();
      this.hidePreview();
      this.hideActions();

      try {
        const response = await this.makeRequest('/generate', data);

        if (response.success) {
          this.state.generatedPostId = response.data?.post_id || null;
          this.showToast('Page generated successfully!', 'success');

          const hasDedicatedPreview = !!document.getElementById('aipb-preview-container');
          if (hasDedicatedPreview && (response.data?.preview_url || response.data?.content)) {
            this.showPreview(response.data);
            this.showActions();
          } else {
            const target = response.data?.editor === 'elementor'
              ? (response.data?.elementor_edit_url || response.data?.edit_url)
              : response.data?.edit_url;
            this.showToast('Content created successfully. Opening the editor...', 'success');
            if (target) {
              window.location.href = target;
              return;
            }
          }
        } else {
          const msg = response.data?.message || 'Generation failed. Please try again.';
          this.showToast(msg, 'error');
        }
      } catch (err) {
        console.error('AIPB generate error:', err);
        this.showToast(err.message || 'An unexpected error occurred.', 'error');
      } finally {
        this.state.generating = false;
        this.setButtonLoading(false);
      }
    },

    setButtonLoading(loading) {
      const btn = document.getElementById('aipb-generate-btn');
      if (!btn) return;
      btn.disabled = loading;
      if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="aipb-spinner"></span> Generating...';
      } else {
        btn.innerHTML = btn.dataset.originalText || 'Generate with AI';
      }
    },

    showProgress() {
      const container = document.getElementById('aipb-progress-container');
      if (container) {
        container.classList.add('active');
      }
      this.state.currentStep = 0;
      this.buildStepsUI();
      this.simulateSteps();
    },

    buildStepsUI() {
      const wrapper = document.getElementById('aipb-steps-container');
      if (!wrapper) return;
      wrapper.innerHTML = '';
      this.steps.forEach((step, i) => {
        const el = document.createElement('div');
        el.className = 'aipb-step' + (i === 0 ? ' active' : '');
        el.innerHTML = `
          <div class="aipb-step-icon">${step.icon}</div>
          <div class="aipb-step-label">${step.label}</div>
        `;
        wrapper.appendChild(el);
      });
    },

    simulateSteps() {
      const totalSteps = this.steps.length;
      let current = 0;

      const advance = () => {
        if (current >= totalSteps || !this.state.generating) return;
        this.updateProgress(current);
        current++;
        const delay = 800 + Math.random() * 1200;
        setTimeout(advance, delay);
      };

      advance();
    },

    updateProgress(stepIndex) {
      const steps = document.querySelectorAll('#aipb-steps-container .aipb-step');
      const bar = document.getElementById('aipb-progress-bar');

      steps.forEach((el, i) => {
        el.classList.remove('active', 'complete');
        if (i < stepIndex) {
          el.classList.add('complete');
        } else if (i === stepIndex) {
          el.classList.add('active');
        }
      });

      if (bar) {
        const pct = ((stepIndex + 1) / this.steps.length) * 100;
        bar.style.width = pct + '%';
      }

      this.state.currentStep = stepIndex;
    },

    hideProgress() {
      const steps = document.querySelectorAll('#aipb-steps-container .aipb-step');
      steps.forEach((el, i) => {
        if (i < this.steps.length) {
          el.classList.remove('active');
          el.classList.add('complete');
        }
      });

      const bar = document.getElementById('aipb-progress-bar');
      if (bar) bar.style.width = '100%';

      setTimeout(() => {
        const container = document.getElementById('aipb-progress-container');
        if (container) container.classList.remove('active');
      }, 800);
    },

    showPreview(data) {
      const container = document.getElementById('aipb-preview-container');
      if (container) container.classList.add('active');
      this.state.previewVisible = true;
      this.renderPreview(data);
      this.hideProgress();
    },

    renderPreview(data) {
      const frame = document.getElementById('aipb-preview-inner');
      if (!frame) return;

      if (data.preview_url) {
        frame.innerHTML = `<iframe src="${this.escapeHtml(data.preview_url)}" style="width:100%;height:600px;border:none;"></iframe>`;
        return;
      }

      if (data.content) {
        frame.innerHTML = `<div style="padding:24px;max-width:100%;overflow:auto;">${data.content}</div>`;
        return;
      }

      frame.innerHTML = '<div class="aipb-empty-state"><p>No preview available.</p></div>';
    },

    switchDevice(device) {
      const frame = document.getElementById('aipb-preview-frame');
      if (!frame) return;

      frame.classList.remove('tablet', 'mobile');
      if (device === 'tablet') frame.classList.add('tablet');
      if (device === 'mobile') frame.classList.add('mobile');

      document.querySelectorAll('.aipb-device-btn').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.device === device);
      });
    },

    hidePreview() {
      const container = document.getElementById('aipb-preview-container');
      if (container) container.classList.remove('active');
      this.state.previewVisible = false;
    },

    showActions() {
      const el = document.getElementById('aipb-action-buttons');
      if (el) el.style.display = 'flex';
    },

    hideActions() {
      const el = document.getElementById('aipb-action-buttons');
      if (el) el.style.display = 'none';
    },

    async createDraft() {
      if (!this.state.generatedPostId) {
        this.showToast('No generated page to create a draft from.', 'warning');
        return;
      }
      window.location.href = `/wp-admin/post.php?post=${this.state.generatedPostId}&action=edit`;
    },

    async publishPage() {
      if (!this.state.generatedPostId) {
        this.showToast('No generated page to publish.', 'warning');
        return;
      }

      if (!confirm('Are you sure you want to publish this page?')) return;

      try {
        const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};
        const url = (vars.apiUrl || '/wp-json/promptpress-ai/v1') + '/generate';
        const res = await fetch(`/wp-json/wp/v2/pages/${this.state.generatedPostId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': vars.nonce || '',
          },
          credentials: 'same-origin',
          body: JSON.stringify({ status: 'publish' }),
        });
        if (res.ok) {
          this.showToast('Page published!', 'success');
          window.open(`/wp-admin/post.php?post=${this.state.generatedPostId}&action=edit`, '_blank');
        } else {
          this.showToast('Failed to publish page.', 'error');
        }
      } catch (err) {
        this.showToast(err.message || 'Error publishing page.', 'error');
      }
    },

    editInGutenberg() {
      if (!this.state.generatedPostId) return;
      window.location.href = `/wp-admin/post.php?post=${this.state.generatedPostId}&action=edit`;
    },

    editInElementor() {
      if (!this.state.generatedPostId) return;
      window.location.href = `/wp-admin/post.php?post=${this.state.generatedPostId}&action=elementor`;

    },

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

      const icons = {
        success: '&#10003;',
        error: '&#10007;',
        warning: '&#9888;',
        info: '&#8505;',
      };

      toast.innerHTML = `<span>${icons[type] || icons.info}</span><span>${this.escapeHtml(message)}</span>`;
      container.appendChild(toast);

      setTimeout(() => {
        toast.classList.add('removing');
        toast.addEventListener('animationend', () => toast.remove());
      }, 4000);
    },

    async makeRequest(endpoint, data = {}) {
      const vars = typeof aipbAdmin !== 'undefined' ? aipbAdmin : {};

      const baseUrl = vars.apiUrl || '/wp-json/promptpress-ai/v1';
      const url = baseUrl + endpoint;

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': vars.nonce || '',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
      });

      const text = await res.text();
      let result = {};

      try {
        result = text ? JSON.parse(text) : {};
      } catch (e) {
        throw new Error(
          `WordPress returned an invalid response (HTTP ${res.status}).`,
        );
      }

      if (!res.ok) {
        throw new Error(
          result.message ||
            result.data?.message ||
            `HTTP ${res.status}`,
        );
      }

      return result;
    },

    escapeHtml(str) {
      if (!str) return '';
      const div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    },
  };

  document.addEventListener('DOMContentLoaded', () => BuilderApp.init());
})();
