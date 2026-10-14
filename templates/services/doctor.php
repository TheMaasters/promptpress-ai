<?php
/**
 * Template: Doctor Page.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'services_doctor',
	'name'        => 'Doctor Page',
	'category'    => 'services',
	'description' => 'Medical practice homepage with services, doctor profiles, patient resources and appointment booking',
	'prompt'      => 'Create a medical practice homepage with: clean hero section with practice name, tagline about patient care and appointment CTA, services section listing medical specialties and treatments offered (general practice, preventive care, diagnostics, specialist referrals) with descriptions, physician profiles section featuring 3 doctors with photos, medical credentials and specialties, patient resources section with links to forms, insurance information and FAQs, testimonials section with 3 patient reviews emphasizing care quality and outcomes, office information section with location map placeholder, office hours and contact details, and a new patient registration section. Use a calming medical color palette of soft blues and whites, clean sans-serif typography, and a layout that conveys professionalism and trustworthiness.',
	'config'      => [
		'content_type'  => 'page',
		'editor'        => 'gutenberg',
		'tone'          => 'caring',
		'design_style'  => 'clean',
	],
];
