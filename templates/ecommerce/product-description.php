<?php
/**
 * Template: Product Description.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'ecommerce_product_description',
	'name'        => 'Product Description',
	'category'    => 'ecommerce',
	'description' => 'Detailed product description with specifications, benefits and persuasive copy',
	'prompt'      => 'Create a detailed product description page with: compelling product headline and subheadline, product overview paragraph highlighting the main value proposition, key benefits section with 5-6 bullet points explaining how the product solves customer problems, detailed product specifications in an organized list or table, material and care information section, size guide or dimensions section if applicable, shipping and returns information, related products section with 4 product suggestions, and a clear call-to-action to purchase. Write in an engaging, benefit-focused style that emphasizes the product advantages and creates desire.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'persuasive',
		'design_style'  => 'clean',
	],
];
