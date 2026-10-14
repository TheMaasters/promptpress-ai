<?php
/**
 * Template: Startup Homepage.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'business_startup',
	'name'        => 'Startup Homepage',
	'category'    => 'business',
	'description' => 'Modern startup homepage with problem-solution narrative, product demo and traction metrics',
	'prompt'      => 'Create a modern startup homepage with: hero section featuring a compelling problem-solution headline with product screenshot or demo video placeholder, traction metrics bar showing key stats (users, growth rate, funding), how-it-works section with a 3-step visual process explaining the product workflow, key differentiators section comparing the solution against alternatives with a feature comparison, press and media mentions section, beta signup or early access CTA section with email capture form, and a footer with social links. Use a clean startup aesthetic with accent gradients, card-based layouts, and data-driven visuals.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'energetic',
		'design_style'  => 'modern',
	],
];
