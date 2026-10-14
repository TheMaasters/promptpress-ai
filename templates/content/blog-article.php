<?php
/**
 * Template: Blog Article.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'content_blog_article',
	'name'        => 'Blog Article',
	'category'    => 'content',
	'description' => 'In-depth blog article with structured sections, rich content and engagement elements',
	'prompt'      => 'Create an in-depth blog article with: compelling headline optimized for SEO, author byline with publication date, featured image placeholder, engaging introduction paragraph that hooks the reader, well-structured body content with 5-6 H2 and H3 subheadings creating a logical information hierarchy, key takeaways or summary section, practical tips or actionable advice throughout, relevant examples or case references to support points, internal linking opportunities placeholder, conclusion paragraph with key message reinforcement, author bio section with brief description, related articles section suggesting 3-4 additional posts, and a comments or engagement CTA section. Write in an informative yet accessible style with clear paragraphs, scannable formatting and value-driven content.',
	'config'      => [
		'content_type'  => 'post',
		'editor'        => 'gutenberg',
		'tone'          => 'informative',
		'design_style'  => 'minimal',
	],
];
