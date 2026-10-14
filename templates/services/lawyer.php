<?php
/**
 * Template: Lawyer Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_lawyer',
	'name'        => 'Lawyer Page',
	'category'    => 'services',
	'description' => 'Law firm homepage with practice areas, attorney profiles, case results and consultation CTA',
	'prompt'      => 'Create a law firm homepage with: authoritative hero section with firm name, tagline emphasizing experience and results, and free consultation CTA, practice areas section detailing 4-6 legal specialties (personal injury, family law, criminal defense, corporate law, estate planning, immigration) with brief descriptions, notable case results or settlement statistics section, attorney profiles section featuring 3-4 lawyers with photos, credentials and practice focus, client testimonials section with 3 reviews emphasizing trust and outcomes, resources section with links to legal guides or blog posts, and a contact section with office address and consultation booking form. Use a trustworthy color palette of deep navy, gold accents, and formal typography that conveys expertise and credibility.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'authoritative',
		'design_style'  => 'formal',
	],
];
