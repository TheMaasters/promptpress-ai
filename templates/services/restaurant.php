<?php
/**
 * Template: Restaurant Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_restaurant',
	'name'        => 'Restaurant Page',
	'category'    => 'services',
	'description' => 'Restaurant homepage with menu highlights, reservations, ambiance showcase and reviews',
	'prompt'      => 'Create a restaurant homepage with: inviting hero section with restaurant name, tagline and ambient background image placeholder, signature dishes section showcasing 4-6 menu highlights with dish names, descriptions and prices, full menu section with categorized items (appetizers, mains, desserts, drinks), about section telling the restaurant story and chef philosophy, reservation section with booking CTA and opening hours, location and contact section with address and phone number, customer reviews section with 3 testimonials, and a photo gallery section showcasing the restaurant ambiance. Use warm, appetizing colors, elegant typography, and a food-forward design that evokes the dining experience.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'warm',
		'design_style'  => 'elegant',
	],
];
