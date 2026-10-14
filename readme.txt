=== PromptPress AI ===
Contributors: themaasters
Tags: ai, content-generation, gutenberg, elementor, page-builder
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate WordPress pages and posts from natural-language prompts and keep the result editable in Gutenberg or Elementor.

== Description ==

PromptPress AI adds an AI-assisted content generation workflow to WordPress. Describe the page or post you want, choose Gutenberg or Elementor, and the plugin generates editable WordPress content.

The plugin uses an AI provider API configured by the site administrator. The current built-in provider is OpenAI.

**Key Features:**

* Prompt-based page and post generation
* Native Gutenberg block output
* Elementor document generation with a native PromptPress AI Elementor widget
* AI rewrite and content improvement actions in Gutenberg
* Brand voice and color settings
* Generation history and usage tracking
* Secure server-side API requests
* API connection testing
* Optional AI image generation/import support
* SEO metadata integrations where supported

**AI Provider:**

* OpenAI API

== Third-Party Services ==

PromptPress AI can connect to third-party AI services configured by the site administrator. The current built-in provider is OpenAI. The plugin does not contact OpenAI automatically on page loads or send telemetry. Requests are made when an administrator explicitly configures an API key and uses an AI feature such as generation, connection testing, or image generation.

When OpenAI is configured, PromptPress AI sends the prompts, content, and generation instructions required for the requested operation to OpenAI's API. The API key is stored in the WordPress site's options and is used server-side; it is not exposed to public site visitors.

OpenAI service terms: https://openai.com/policies/service-terms/
OpenAI services agreement: https://openai.com/policies/services-agreement/
OpenAI privacy policy: https://openai.com/policies/privacy-policy/

Site owners are responsible for reviewing the current terms and privacy requirements of the configured provider and for informing their users where required.

== Screenshots ==

1. AI Settings screen for configuring the AI provider, models, API key, and generation options.
2. Prompt Templates library with categories and ready-to-use page and post prompts.

== Privacy ==

PromptPress AI stores generation history locally in the WordPress database when generation requests are recorded. Depending on the site's configuration, this can include the prompt, post ID, post type, editor type, provider, model, status, token/usage information, timestamps, and error information. This local history is not transmitted to The Maasters for analytics or advertising.

PromptPress AI does not include third-party analytics, advertising trackers, or telemetry services. It does not sell site data.

The plugin also adds a privacy-policy suggestion in WordPress to help site administrators document AI data transfers. Site owners should review and adapt that policy to their specific use case and applicable laws.

== Installation ==

1. Upload the `promptpress-ai` folder to the `/wp-content/plugins/` directory or install the ZIP from the WordPress Plugins screen.
2. Activate the plugin.
3. Open **PromptPress AI > AI Settings**.
4. Select an AI provider and enter its API key.
5. Click **Test Connection**.
6. Save the settings.
7. Generate a page or post from the PromptPress AI builder or from a supported editor.

Elementor integration is enabled automatically when Elementor is active.

== Frequently Asked Questions ==

= Which AI provider is supported? =

The built-in provider is OpenAI. The provider layer is extensible so additional providers can be added later.

= Does it work with Gutenberg? =

Yes. PromptPress AI generates native Gutenberg block markup and the editor integration parses that markup into real blocks, so the generated content remains editable in the normal WordPress editor.

= Does it work with Elementor? =

Yes, when Elementor is active. PromptPress AI registers a native Elementor widget and can create Elementor drafts containing native Elementor containers and widgets. Those drafts remain editable in Elementor.

= Does it automatically publish content? =

No. Generated pages and posts default to Draft unless the administrator explicitly chooses another status.

= Is my API key exposed to visitors? =

No. AI requests are made server-side. The API key is not placed in frontend page content.

= Does the plugin send data to third parties? =

AI generation requests are sent to the configured AI provider. Site owners should review the provider's policies and their own privacy requirements.

== Changelog ==

= 1.0.7 =
* Added WordPress.org screenshot documentation for the plugin directory.
* Prepared plugin directory icon assets separately from the plugin runtime files.
* Refined readme metadata for WordPress.org submission.

= 1.0.6 =
* Updated WordPress.org metadata for the The Maasters contributor account.
* Reduced readme tags to five and improved third-party service/privacy disclosures.
* Documented local generation history and confirmed that PromptPress AI does not include telemetry or advertising trackers.
* Added current OpenAI service terms and services agreement references.

= 1.0.5 =
* Added Local Demo Mode for testing editor integrations without AI API usage.
* Added premium template library visuals, category navigation, preview modal, and brand-aware template prompts.
* Fixed fresh-install generation history schema and added database upgrade migration.
* Improved settings persistence for boolean options and connection testing in Demo Mode.

= 1.0.4 =
* Fixed REST API route registration causing 404 responses.
* Fixed settings save and connection testing flow.
* Added robust invalid-response handling in the admin JavaScript.
* Fixed Gutenberg insertion to preserve native block structure.
* Added native Elementor widget registration.
* Fixed Elementor document data storage format.
* Fixed Elementor draft generation and editor integration.
* Added a WordPress privacy-policy suggestion.
* Updated readme compatibility information.

= 1.0.0 =
* Initial development release.

== Upgrade Notice ==

= 1.0.7 =
* Submission-readiness update with documented screenshots and directory asset preparation.

= 1.0.6 =
* Compliance-focused metadata and privacy/service documentation update for WordPress.org submission.

= 1.0.5 =
* Added Local Demo Mode for testing editor integrations without AI API usage.
* Added premium template library visuals, category navigation, preview modal, and brand-aware template prompts.
* Fixed fresh-install generation history schema and added database upgrade migration.
* Improved settings persistence for boolean options and connection testing in Demo Mode.

= 1.0.4 =
Important functional fixes for REST, settings, Gutenberg, and Elementor integration.
