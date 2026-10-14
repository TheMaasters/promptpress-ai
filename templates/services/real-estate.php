<?php
/**
 * Template: Real Estate Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_real_estate',
	'name'        => 'Real Estate Page',
	'category'    => 'services',
	'description' => 'Real estate agent or agency homepage with property listings, area guides and contact',
	'prompt'      => 'Create a real estate agent or agency homepage with: hero section featuring a property search bar with location, price range and property type filters, featured listings section showcasing 6 properties in a card grid with property images, price, bedrooms, bathrooms and square footage, agent profile section with photo, name, certifications and contact information, area guide section highlighting neighborhoods with brief descriptions of lifestyle, schools and amenities, recent sales or market statistics section showing performance metrics, testimonials section with 3 client reviews about buying or selling experience, mortgage calculator widget section, and a contact section with office details and scheduling form. Use a clean real estate aesthetic with property-focused imagery, card-based layouts, and a trustworthy color palette of whites, grays and accent blues.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'trustworthy',
		'design_style'  => 'clean',
	],
];
