<?php
/**
 * Template: Product Landing Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'ecommerce_product_landing',
	'name'        => 'Product Landing Page',
	'category'    => 'ecommerce',
	'description' => 'Product launch landing page with hero, features, specs, reviews and buy CTA',
	'prompt'      => 'Create a product landing page for an ecommerce product with: hero section featuring product name, tagline, hero image placeholder and add-to-cart CTA button, product overview section with 3 key selling points using icons, detailed features section with alternating image-text layout showcasing 4 product features, technical specifications section presented in a clean table format, customer reviews section with star ratings and 3 testimonials, bundle or upsell section suggesting complementary products, and a sticky add-to-cart section. Use a clean product-focused design with high contrast, product imagery emphasis, and purchase-oriented layout.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'compelling',
		'design_style'  => 'clean',
	],
];
