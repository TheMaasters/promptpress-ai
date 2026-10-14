<?php
/**
 * Template: Review.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'content_review',
	'name'        => 'Review',
	'category'    => 'content',
	'description' => 'Comprehensive product or service review with pros, cons, ratings and verdict',
	'prompt'      => 'Create a comprehensive product or service review with: review headline including product name and verdict summary, product overview section with name, price, category and overall rating, at-a-glance summary box with key specs and quick rating breakdown (value, quality, features, ease of use), detailed review sections covering 4-5 key aspects of the product with in-depth analysis and opinions, pros section with 5-6 highlighted advantages with explanations, cons section with 3-4 noted disadvantages with context, comparison section benchmarking against 2-3 competitors, who is this product for section defining ideal use cases and user types, frequently asked questions section with 4-5 common queries, final verdict section with overall recommendation and score, and affiliate disclosure or editorial standards note. Write in a balanced, honest tone that builds trust through transparent assessment.',
	'config'      => [
		'content_type'  => 'post',
		'editor'        => 'gutenberg',
		'tone'          => 'objective',
		'design_style'  => 'minimal',
	],
];
