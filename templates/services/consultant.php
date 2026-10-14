<?php
/**
 * Template: Consultant Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_consultant',
	'name'        => 'Consultant Page',
	'category'    => 'services',
	'description' => 'Consultant or coach homepage with expertise areas, process overview, case studies and booking',
	'prompt'      => 'Create a consultant or coach homepage with: professional hero section with consultant name, professional title and compelling tagline, expertise areas section highlighting 3-4 consulting specializations (strategy, operations, leadership, digital transformation) with detailed descriptions, my process section explaining the consulting methodology in 4 clear steps (discovery, analysis, strategy, implementation), case studies section featuring 3 success stories with client challenges, solutions and measurable results, about section with professional background, credentials and philosophy, client logos or industry recognition section, testimonials section with 3 client endorsements, and a book a consultation section with scheduling CTA. Use a premium professional aesthetic with sophisticated colors, structured layouts and authoritative typography.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'professional',
		'design_style'  => 'premium',
	],
];
