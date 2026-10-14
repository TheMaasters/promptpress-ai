// AI Page Builder - Gutenberg Block Editor Integration
(function () {
  "use strict";

  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost || {};
  const { __ } = wp.i18n;
  const {
    PanelBody,
    TextareaControl,
    SelectControl,
    Button,
    Spinner,
    Notice,
    Flex,
    FlexItem,
  } = wp.components;
  const { useSelect, useDispatch } = wp.data;
  const { useState, useCallback } = wp.element;
  const { createBlock, replaceBlock, insertBlock } = wp.blocks;
  const { useBlockProps } = wp.blockEditor || {};
  const apiFetch = wp.apiFetch;

  const ACTION_OPTIONS = [
    { label: "Generate Content", value: "generate" },
    { label: "Add Section", value: "add_section" },
    { label: "Rewrite", value: "rewrite" },
    { label: "Improve", value: "improve" },
    { label: "Expand", value: "expand" },
    { label: "Summarize", value: "summarize" },
    { label: "Translate", value: "translate" },
  ];

  const TONE_OPTIONS = [
    { label: "Professional", value: "professional" },
    { label: "Casual", value: "casual" },
    { label: "Friendly", value: "friendly" },
    { label: "Formal", value: "formal" },
    { label: "Witty", value: "witty" },
    { label: "Persuasive", value: "persuasive" },
  ];

  const LANGUAGE_OPTIONS = [
    { label: "English", value: "en" },
    { label: "Spanish", value: "es" },
    { label: "French", value: "fr" },
    { label: "German", value: "de" },
    { label: "Portuguese", value: "pt" },
    { label: "Italian", value: "it" },
    { label: "Dutch", value: "nl" },
    { label: "Japanese", value: "ja" },
    { label: "Chinese", value: "zh" },
  ];

  /**
   * Main AI Panel Component
   */
  function AIPBPanel() {
    const [prompt, setPrompt] = useState("");
    const [action, setAction] = useState("generate");
    const [tone, setTone] = useState("professional");
    const [language, setLanguage] = useState("en");
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");

    const selectedBlock = useSelect((select) => {
      const block = select("core/block-editor")?.getSelectedBlock();
      return block || null;
    }, []);

    const { replaceBlock, insertBlock } = useDispatch("core/block-editor");

    const resetMessages = useCallback(() => {
      setError("");
      setSuccess("");
    }, []);

    /**
     * Convert HTML content into Gutenberg blocks.
     */
    /**
     * Convert native Gutenberg block markup returned by the server into
     * real editor blocks. This preserves groups, columns, buttons, cover,
     * images and other nested blocks instead of flattening them to HTML.
     */
    const htmlToBlocks = useCallback((content) => {
      if (!content) return [];

      try {
        const parsed = wp.blocks.parse(content);
        if (Array.isArray(parsed) && parsed.length) {
          return parsed;
        }
      } catch (parseError) {
        console.error("PromptPress AI: failed to parse block markup.", parseError);
      }

      // Safe fallback for providers/older responses that return plain HTML.
      const temp = document.createElement("div");
      temp.innerHTML = content;
      const blocks = [];

      Array.from(temp.children).forEach((node) => {
        const tag = node.tagName;
        if (/^H[1-6]$/.test(tag)) {
          blocks.push(
            createBlock("core/heading", {
              level: parseInt(tag.substring(1), 10),
              content: node.innerHTML,
            }),
          );
        } else if (tag === "P") {
          blocks.push(createBlock("core/paragraph", { content: node.innerHTML }));
        } else if (tag === "UL" || tag === "OL") {
          blocks.push(
            createBlock("core/list", {
              ordered: tag === "OL",
              values: Array.from(node.querySelectorAll(":scope > li")).map((li) => ({
                content: li.innerHTML,
              })),
            }),
          );
        } else if (tag === "IMG") {
          blocks.push(
            createBlock("core/image", {
              url: node.getAttribute("src") || "",
              alt: node.getAttribute("alt") || "",
            }),
          );
        } else if (node.outerHTML.trim()) {
          blocks.push(createBlock("core/html", { content: node.outerHTML }));
        }
      });

      return blocks;
    }, []);

    /**
     * Main generate handler
     */
    const handleGenerate = useCallback(async () => {
      resetMessages();

      const trimmed = prompt.trim();
      if (!trimmed) {
        setError("Please enter a prompt.");
        return;
      }

      setLoading(true);

      try {
        const payload = {
          prompt: trimmed,
          content_type: "page",
          editor: "gutenberg",
          content_only: true,
          tone,
          language,
        };

        const response = await apiFetch({
          path: "/promptpress-ai/v1/generate",
          method: "POST",
          data: payload,
        });

        if (!response.success) {
          setError(response.data?.message || "Generation failed.");
          setLoading(false);
          return;
        }

        const content = response.data?.content || "";

        if (action === "add_section" || action === "generate") {
          const blocks = htmlToBlocks(content);
          if (blocks.length > 0) {
            // Insert generated sections at the editor root. Passing the selected
            // paragraph/clientId as rootClientId can create illegal nesting and
            // trigger Gutenberg's "This block contains unexpected or invalid content" error.
            blocks.forEach((block) => {
              insertBlock(block);
            });
          } else {
            insertBlock(createBlock("core/paragraph", { content }));
          }
          setSuccess("Content added to the editor.");
        } else if (
          selectedBlock &&
          ["rewrite", "improve", "expand", "summarize"].includes(action)
        ) {
          if (
            selectedBlock.name === "core/paragraph" ||
            selectedBlock.name === "core/heading"
          ) {
            replaceBlock(
              selectedBlock.clientId,
              createBlock(selectedBlock.name, {
                ...selectedBlock.attributes,
                content,
              }),
            );
          } else {
            const newBlock = createBlock("core/html", { content });
            replaceBlock(selectedBlock.clientId, newBlock);
          }
          setSuccess("Block content updated.");
        } else {
          const blocks = htmlToBlocks(content);
          if (blocks.length > 0) {
            blocks.forEach((block) => insertBlock(block));
          }
          setSuccess("Content added to the editor.");
        }

        setPrompt("");
      } catch (err) {
        console.error("AIPB Gutenberg error:", err);
        setError(err.message || "An unexpected error occurred.");
      } finally {
        setLoading(false);
      }
    }, [
      prompt,
      action,
      tone,
      language,
      selectedBlock,
      resetMessages,
      htmlToBlocks,
      replaceBlock,
      insertBlock,
    ]);

    /**
     * Insert a predefined prompt from the toolbar dropdown
     */
    const insertQuickPrompt = useCallback((quickPrompt) => {
      setPrompt(quickPrompt);
    }, []);

    return wp.element.createElement(
      PluginDocumentSettingPanel,
      {
        name: "aipb-ai-panel",
        title: __("PromptPress AI", "promptpress-ai"),
        icon: "magic",
        className: "aipb-gutenberg-panel",
      },
      error &&
        wp.element.createElement(
          Notice,
          {
            status: "error",
            isDismissible: true,
            onDismiss: resetMessages,
          },
          error,
        ),
      success &&
        wp.element.createElement(
          Notice,
          {
            status: "success",
            isDismissible: true,
            onDismiss: resetMessages,
          },
          success,
        ),
      wp.element.createElement(TextareaControl, {
        label: __("Prompt", "promptpress-ai"),
        value: prompt,
        onChange: setPrompt,
        placeholder: __(
          "Describe what you want to create or modify...",
          "promptpress-ai",
        ),
        rows: 4,
        help: selectedBlock
          ? __(
              "Selected block content will be used as context for rewrite/improve actions.",
              "promptpress-ai",
            )
          : null,
      }),
      wp.element.createElement(SelectControl, {
        label: __("Action", "promptpress-ai"),
        value: action,
        options: ACTION_OPTIONS,
        onChange: setAction,
      }),
      wp.element.createElement(SelectControl, {
        label: __("Tone", "promptpress-ai"),
        value: tone,
        options: TONE_OPTIONS,
        onChange: setTone,
      }),
      action === "translate" &&
        wp.element.createElement(SelectControl, {
          label: __("Target Language", "promptpress-ai"),
          value: language,
          options: LANGUAGE_OPTIONS,
          onChange: setLanguage,
        }),
      wp.element.createElement(
        Flex,
        { justify: "space-between", className: "aipb-panel-actions" },
        wp.element.createElement(
          FlexItem,
          null,
          wp.element.createElement(
            Button,
            {
              isPrimary: true,
              onClick: handleGenerate,
              disabled: loading || !prompt.trim(),
              isBusy: loading,
            },
            loading
              ? wp.element.createElement(Spinner, { key: "spinner" })
              : __("Generate", "promptpress-ai"),
          ),
        ),
      ),
    );
  }

  /**
   * Quick Prompt Dropdown for the Block Editor Toolbar
   */
  function AIPBToolbarButton() {
    const [isOpen, setIsOpen] = useState(false);

    const quickPrompts = [
      {
        label: "Expand this section",
        prompt:
          "Expand and elaborate on this section with more detail and supporting information.",
      },
      {
        label: "Make it concise",
        prompt:
          "Rewrite this content to be more concise while keeping the key message.",
      },
      {
        label: "Add FAQ section",
        prompt:
          "Add a relevant FAQ section with 5 common questions and answers.",
      },
      {
        label: "Improve readability",
        prompt:
          "Rewrite this to improve readability and flow for a general audience.",
      },
      {
        label: "Add call to action",
        prompt: "Add a compelling call to action at the end of this content.",
      },
      {
        label: "SEO optimize",
        prompt:
          "Optimize this content for SEO with better keyword usage and structure.",
      },
    ];

    return wp.element.createElement(
      wp.blockEditor.BlockControls,
      { key: "aipb-toolbar" },
      wp.element.createElement(
        wp.components.ToolbarGroup,
        null,
        wp.element.createElement(wp.components.ToolbarButton, {
          icon: "magic",
          label: "PromptPress AI",
          onClick: () => setIsOpen(!isOpen),
        }),
      ),
      isOpen &&
        wp.element.createElement(
          wp.components.Popover,
          {
            onClose: () => setIsOpen(false),
            position: "bottom right",
            className: "aipb-toolbar-popover",
          },
          wp.element.createElement(
            "div",
            { className: "aipb-toolbar-menu" },
            wp.element.createElement(
              "div",
              { className: "aipb-toolbar-menu-title" },
              "AI Quick Actions",
            ),
            quickPrompts.map((qp) =>
              wp.element.createElement(
                wp.components.MenuItem,
                {
                  key: qp.label,
                  onClick: () => {
                    // Dispatch to panel or handle inline
                    console.log("AIPB Quick Prompt:", qp.prompt);
                    setIsOpen(false);
                  },
                },
                qp.label,
              ),
            ),
          ),
        ),
    );
  }

  /**
   * Register Gutenberg Plugin
   */
  if (typeof registerPlugin !== "undefined") {
    registerPlugin("aipb-gutenberg-integration", {
      render: () =>
        wp.element.createElement(
          wp.element.Fragment,
          null,
          wp.element.createElement(AIPBPanel),
          wp.element.createElement(AIPBToolbarButton),
        ),
      icon: "magic",
    });
  }
})();
