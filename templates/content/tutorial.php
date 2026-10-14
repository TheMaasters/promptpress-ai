<?php
/**
 * Template: Tutorial.
 *
 * @package AI_Page_Builder
 */

return [
	'id'          => 'content_tutorial',
	'name'        => 'Tutorial',
	'category'    => 'content',
	'description' => 'Step-by-step tutorial with numbered instructions, code examples and troubleshooting',
	'prompt'      => 'Create a step-by-step tutorial with: clear tutorial title indicating what will be learned, prerequisites section listing what readers need before starting (tools, knowledge, accounts), learning objectives section with 3-5 bullet points of what will be achieved, detailed numbered steps (8-12 steps) with clear headings, descriptive instructions and code blocks or command examples where relevant, screenshots or visual placeholder markers at key steps, tips and warnings callout boxes for common pitfalls, troubleshooting section addressing frequent errors and solutions, next steps section suggesting related tutorials or advanced topics, and a feedback or rating section. Write in a clear, instructional tone with concise explanations, consistent formatting and beginner-friendly language.',
	'config'      => [
		'content_type'  => 'post',
		'editor'        => 'gutenberg',
		'tone'          => 'instructional',
		'design_style'  => 'minimal',
	],
];
