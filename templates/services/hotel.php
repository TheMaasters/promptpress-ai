<?php
/**
 * Template: Hotel Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_hotel',
	'name'        => 'Hotel Page',
	'category'    => 'services',
	'description' => 'Hotel homepage with room types, amenities, photo gallery and booking CTA',
	'prompt'      => 'Create a hotel homepage with: luxurious hero section with hotel name, tagline about the guest experience and a check-in/check-out date picker placeholder with book now CTA, room types section showcasing 3-4 room categories (Standard, Deluxe, Suite, Penthouse) with room images, descriptions, amenities and nightly rates, amenities section highlighting hotel features (pool, spa, restaurant, fitness center, concierge, free wifi) with icons, photo gallery section with a grid of interior and exterior hotel images, location section with neighborhood description and nearby attractions, guest reviews section with 3-5 reviews and average rating, special offers section with current deals or packages, and a direct booking section with contact details. Use a hospitality-focused design with warm luxurious colors, elegant serif and sans-serif font pairing, and imagery-forward layout.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'inviting',
		'design_style'  => 'luxurious',
	],
];
