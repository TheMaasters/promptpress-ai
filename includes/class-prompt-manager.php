<?php
/**
 * Prompt Manager - stores and manages AI prompt templates.
 *
 * @package AIPB
 */

namespace AIPB;

defined( 'ABSPATH' ) || exit;

/**
 * Class Prompt_Manager
 *
 * Provides pre-built prompt templates, suggestions, and template
 * variable replacement for the generation UI.
 */
class Prompt_Manager {

	/**
	 * Get prompt templates, optionally filtered by category.
	 *
	 * @param string|null $category Category slug or null for all.
	 *
	 * @return array Associative array of templates.
	 */
	public function get_templates( $category = null ) {
		$templates = $this->get_all_templates();

		if ( null === $category ) {
			return $templates;
		}

		return isset( $templates[ $category ] ) ? $templates[ $category ] : array();
	}

	/**
	 * Retrieve a single template by its ID.
	 *
	 * @param string $template_id Template identifier (e.g. 'business.homepage').
	 *
	 * @return array|null Template array or null when not found.
	 */
	public function get_template( $template_id ) {
		$parts     = explode( '.', $template_id );
		$category  = $parts[0] ?? '';
		$key       = $parts[1] ?? '';
		$templates = $this->get_templates( $category );

		return isset( $templates[ $key ] ) ? $templates[ $key ] : null;
	}

	/**
	 * Return quick-start prompt suggestions for the UI.
	 *
	 * @return array Indexed array of suggestion strings.
	 */
	public function get_suggestions() {
		return array(
			__( 'Create a modern homepage for a SaaS startup', 'promptpress-ai' ),
			__( 'Build a restaurant landing page with menu section', 'promptpress-ai' ),
			__( 'Generate a professional services page for a law firm', 'promptpress-ai' ),
			__( 'Design an ecommerce product landing page', 'promptpress-ai' ),
			__( 'Create a portfolio page for a digital agency', 'promptpress-ai' ),
			__( 'Build a medical practice page with appointment CTA', 'promptpress-ai' ),
			__( 'Generate a real estate property listing page', 'promptpress-ai' ),
			_( 'Create a blog article about technology trends', 'promptpress-ai' ),
			__( 'Build a hotel booking landing page', 'promptpress-ai' ),
			__( 'Design a consultant profile and services page', 'promptpress-ai' ),
			__( 'Create an FAQ page for an online product', 'promptpress-ai' ),
			__( 'Build a startup pitch deck style landing page', 'promptpress-ai' ),
		);
	}

	/**
	 * Fill template variables inside a prompt template.
	 *
	 * @param string $template_id    Template identifier.
	 * @param array  $customizations Key => value replacements.
	 *
	 * @return string|\WP_Error Filled prompt or error.
	 */
	public function build_prompt_from_template( string $template_id, array $customizations = array() ) {
		$template = $this->get_template( $template_id );

		if ( null === $template ) {
			return new \WP_Error(
				'aipb_template_not_found',
				__( 'The requested prompt template was not found.', 'promptpress-ai' )
			);
		}

		$prompt = $template['prompt'];

		if ( ! empty( $customizations['industry'] ) ) {
			$prompt = str_replace( '{industry}', sanitize_text_field( $customizations['industry'] ), $prompt );
		}
		if ( ! empty( $customizations['brand_name'] ) ) {
			$prompt = str_replace( '{brand_name}', sanitize_text_field( $customizations['brand_name'] ), $prompt );
		}
		if ( ! empty( $customizations['tone'] ) ) {
			$prompt = str_replace( '{tone}', sanitize_text_field( $customizations['tone'] ), $prompt );
		}
		if ( ! empty( $customizations['pages'] ) ) {
			$prompt = str_replace( '{pages}', sanitize_text_field( $customizations['pages'] ), $prompt );
		}
		if ( ! empty( $customizations['features'] ) ) {
			$prompt = str_replace( '{features}', sanitize_text_field( $customizations['features'] ), $prompt );
		}
		if ( ! empty( $customizations['color_scheme'] ) ) {
			$prompt = str_replace( '{color_scheme}', sanitize_text_field( $customizations['color_scheme'] ), $prompt );
		}
		if ( ! empty( $customizations['language'] ) ) {
			$prompt = str_replace( '{language}', sanitize_text_field( $customizations['language'] ), $prompt );
		}
		if ( ! empty( $customizations['product_name'] ) ) {
			$prompt = str_replace( '{product_name}', sanitize_text_field( $customizations['product_name'] ), $prompt );
		}
		if ( ! empty( $customizations['description'] ) ) {
			$prompt = str_replace( '{description}', sanitize_text_field( $customizations['description'] ), $prompt );
		}

		return $prompt;
	}

	/**
	 * Return the full template library.
	 *
	 * @return array Nested template data.
	 */
	private function get_all_templates() {
		return array(
			'business'   => $this->get_business_templates(),
			'ecommerce'  => $this->get_ecommerce_templates(),
			'services'   => $this->get_services_templates(),
			'content'    => $this->get_content_templates(),
		);
	}

	/**
	 * Business category templates.
	 *
	 * @return array
	 */
	private function get_business_templates() {
		return array(
			'homepage' => array(
				'name'        => __( 'Business Homepage', 'promptpress-ai' ),
				'description' => __( 'A professional homepage for a general business.', 'promptpress-ai' ),
				'category'    => 'business',
				'variables'   => array( '{industry}', '{brand_name}', '{tone}' ),
				'prompt'      => "Create a professional homepage for a {industry} business called \"{brand_name}\".\n\nThe tone should be {tone}.\n\nInclude the following sections:\n1. Hero section with headline, subheadline, and a primary call-to-action button\n2. Features/benefits section with 3-4 key points\n3. About section with company overview\n4. Testimonials section with 3 customer quotes\n5. Call-to-action section\n6. Footer with contact info\n\nOutput the content as a structured JSON array with 'sections' containing each section's type, heading, and content.",
			),
			'saas_landing' => array(
				'name'        => __( 'SaaS Landing Page', 'promptpress-ai' ),
				'description' => __( 'A conversion-focused landing page for a SaaS product.', 'promptpress-ai' ),
				'category'    => 'business',
				'variables'   => array( '{brand_name}', '{features}', '{tone}' ),
				'prompt'      => "Create a high-converting SaaS landing page for \"{brand_name}\".\n\nThe tone should be {tone}.\n\nKey features to highlight: {features}\n\nInclude the following sections:\n1. Hero with headline, subheadline, email signup or CTA button\n2. Social proof bar (logos or stats)\n3. Features section with icons and descriptions (at least 4)\n4. How it works (3-step process)\n5. Pricing section with 3 tiers\n6. FAQ section with 5-6 questions\n7. Final CTA section\n\nOutput as structured JSON with 'sections' array.",
			),
			'agency' => array(
				'name'        => __( 'Agency Landing Page', 'promptpress-ai' ),
				'description' => __( 'A portfolio-style page for a creative or digital agency.', 'promptpress-ai' ),
				'category'    => 'business',
				'variables'   => array( '{brand_name}', '{industry}', '{tone}' ),
				'prompt'      => "Create an agency landing page for \"{brand_name}\", a {industry} agency.\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with bold headline and portfolio preview\n2. Services offered (grid of 4-6 services)\n3. Portfolio/case studies section\n4. Team section with 3-4 team members\n5. Client testimonials\n6. Contact form section\n7. Call-to-action\n\nOutput as structured JSON with 'sections' array.",
			),
			'startup' => array(
				'name'        => __( 'Startup Pitch Page', 'promptpress-ai' ),
				'description' => __( 'A bold landing page for an early-stage startup.', 'promptpress-ai' ),
				'category'    => 'business',
				'variables'   => array( '{brand_name}', '{description}', '{tone}' ),
				'prompt'      => "Create a startup landing page for \"{brand_name}\".\n\nProduct description: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with compelling headline, subheadline, and waitlist/CTA button\n2. Problem statement section\n3. Solution overview\n4. Key features (4-6 with icons)\n5. Traction or metrics section\n6. Testimonials or early adopter quotes\n7. Final CTA for signup\n\nOutput as structured JSON with 'sections' array.",
			),
		);
	}

	/**
	 * Ecommerce category templates.
	 *
	 * @return array
	 */
	private function get_ecommerce_templates() {
		return array(
			'product_page' => array(
				'name'        => __( 'Product Page', 'promptpress-ai' ),
				'description' => __( 'A detailed product page with description and features.', 'promptpress-ai' ),
				'category'    => 'ecommerce',
				'variables'   => array( '{product_name}', '{description}', '{features}' ),
				'prompt'      => "Create a product page for \"{product_name}\".\n\nDescription: {description}\n\nKey features: {features}\n\nInclude:\n1. Hero with product name, headline, price, and add-to-cart CTA\n2. Product description section\n3. Key features grid (4-6 features)\n4. Product gallery section\n5. Specifications or details\n6. Customer reviews section (3 reviews)\n7. Related products or upsell section\n8. Final CTA\n\nOutput as structured JSON with 'sections' array.",
			),
			'product_landing' => array(
				'name'        => __( 'Product Landing Page', 'promptpress-ai' ),
				'description' => __( 'A marketing-focused landing page for a single product.', 'promptpress-ai' ),
				'category'    => 'ecommerce',
				'variables'   => array( '{product_name}', '{description}', '{tone}' ),
				'prompt'      => "Create a product landing page for \"{product_name}\".\n\nProduct details: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero section with product name, tagline, and CTA button\n2. Benefits section (3-4 key benefits)\n3. How it works or product overview\n4. Social proof (testimonials or stats)\n5. FAQ section (4-5 questions)\n6. Guarantee or trust signals\n7. Final CTA with pricing\n\nOutput as structured JSON with 'sections' array.",
			),
		);
	}

	/**
	 * Services category templates.
	 *
	 * @return array
	 */
	private function get_services_templates() {
		return array(
			'restaurant' => array(
				'name'        => __( 'Restaurant Page', 'promptpress-ai' ),
				'description' => __( 'A restaurant landing page with menu and reservation CTA.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{industry}', '{tone}' ),
				'prompt'      => "Create a restaurant landing page for \"{brand_name}\".\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with restaurant name, tagline, and reservation CTA\n2. About the restaurant section\n3. Menu highlights (6-8 dishes with descriptions)\n4. Gallery section\n5. Location and hours\n6. Customer reviews\n7. Reservation CTA section\n\nOutput as structured JSON with 'sections' array.",
			),
			'lawyer' => array(
				'name'        => __( 'Law Firm Page', 'promptpress-ai' ),
				'description' => __( 'A professional page for a law firm or attorney.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{features}', '{tone}' ),
				'prompt'      => "Create a professional page for \"{brand_name}\", a law firm.\n\nPractice areas: {features}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with firm name, practice focus, and consultation CTA\n2. About the firm\n3. Practice areas (4-6 areas)\n4. Attorney profiles (3 attorneys)\n5. Case results or statistics\n6. Client testimonials\n7. Contact and consultation CTA\n\nOutput as structured JSON with 'sections' array.",
			),
			'doctor' => array(
				'name'        => __( 'Medical Practice Page', 'promptpress-ai' ),
				'description' => __( 'A page for a medical practice or clinic.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{features}', '{tone}' ),
				'prompt'      => "Create a medical practice page for \"{brand_name}\".\n\nServices offered: {features}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with practice name, speciality, and appointment CTA\n2. About the practice\n3. Services section (4-6 services)\n4. Doctor profiles (3 doctors)\n5. Patient testimonials\n6. Insurance and accepted plans\n7. Appointment booking CTA\n\nOutput as structured JSON with 'sections' array.",
			),
			'consultant' => array(
				'name'        => __( 'Consultant Page', 'promptpress-ai' ),
				'description' => __( 'A profile and services page for a consultant.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{features}', '{tone}' ),
				'prompt'      => "Create a consultant profile page for \"{brand_name}\".\n\nServices: {features}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with name, title, and booking CTA\n2. About/bio section\n3. Services offered (4-6 services)\n4. Expertise or specializations\n5. Testimonials (3 client quotes)\n6. Case studies or results\n7. Contact and booking CTA\n\nOutput as structured JSON with 'sections' array.",
			),
			'real_estate' => array(
				'name'        => __( 'Real Estate Page', 'promptpress-ai' ),
				'description' => __( 'A property listing or real estate agent page.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{description}', '{tone}' ),
				'prompt'      => "Create a real estate page for \"{brand_name}\".\n\nDescription: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with property/agent name and search or CTA\n2. Featured listings (3-4 properties)\n3. Property details section\n4. Neighborhood information\n5. Agent profile\n6. Testimonials from buyers/sellers\n7. Contact form section\n\nOutput as structured JSON with 'sections' array.",
			),
			'hotel' => array(
				'name'        => __( 'Hotel Page', 'promptpress-ai' ),
				'description' => __( 'A hotel or accommodation landing page.', 'promptpress-ai' ),
				'category'    => 'services',
				'variables'   => array( '{brand_name}', '{features}', '{tone}' ),
				'prompt'      => "Create a hotel landing page for \"{brand_name}\".\n\nAmenities: {features}\n\nThe tone should be {tone}.\n\nInclude:\n1. Hero with hotel name, tagline, and booking CTA\n2. About the hotel\n3. Rooms and suites (3 room types)\n4. Amenities section\n5. Gallery section\n6. Guest reviews\n7. Location and nearby attractions\n8. Booking CTA section\n\nOutput as structured JSON with 'sections' array.",
			),
		);
	}

	/**
	 * Content category templates.
	 *
	 * @return array
	 */
	private function get_content_templates() {
		return array(
			'blog_article' => array(
				'name'        => __( 'Blog Article', 'promptpress-ai' ),
				'description' => __( 'A long-form blog article with structured content.', 'promptpress-ai' ),
				'category'    => 'content',
				'variables'   => array( '{description}', '{tone}', '{language}' ),
				'prompt'      => "Write a comprehensive blog article about: {description}\n\nThe tone should be {tone}. The language should be {language}.\n\nInclude:\n1. Engaging headline\n2. Introduction paragraph\n3. At least 4 main sections with headings and subheadings\n4. Key takeaways or summary\n5. Call-to-action or conclusion\n\nOutput as structured JSON with 'sections' array. Each section should have 'type' (heading, paragraph, list, etc.), 'content', and optional 'heading' field.",
			),
			'tutorial' => array(
				'name'        => __( 'Tutorial / How-To', 'promptpress-ai' ),
				'description' => __( 'A step-by-step tutorial or how-to guide.', 'promptpress-ai' ),
				'category'    => 'content',
				'variables'   => array( '{description}', '{tone}' ),
				'prompt'      => "Write a step-by-step tutorial about: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Clear title\n2. Introduction explaining what readers will learn\n3. Prerequisites or requirements\n4. At least 5 numbered steps with detailed instructions\n5. Tips or notes for each step where relevant\n6. Conclusion with next steps\n\nOutput as structured JSON with 'sections' array.",
			),
			'review' => array(
				'name'        => __( 'Product Review', 'promptpress-ai' ),
				'description' => __( 'A detailed product or service review.', 'promptpress-ai' ),
				'category'    => 'content',
				'variables'   => array( '{product_name}', '{description}', '{tone}' ),
				'prompt'      => "Write a detailed review of: {product_name}\n\nDescription: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Review headline\n2. Overview of the product\n3. Key features analysis\n4. Pros and cons\n5. Performance or quality assessment\n6. Value for money\n7. Final verdict with rating\n\nOutput as structured JSON with 'sections' array.",
			),
			'case_study' => array(
				'name'        => __( 'Case Study', 'promptpress-ai' ),
				'description' => __( 'A business case study with results and analysis.', 'promptpress-ai' ),
				'category'    => 'content',
				'variables'   => array( '{brand_name}', '{description}', '{tone}' ),
				'prompt'      => "Write a case study for \"{brand_name}\".\n\nProject description: {description}\n\nThe tone should be {tone}.\n\nInclude:\n1. Case study title\n2. Client overview\n3. Challenge or problem statement\n4. Solution approach\n5. Implementation details\n6. Results with metrics\n7. Key takeaways\n8. Call-to-action for similar services\n\nOutput as structured JSON with 'sections' array.",
			),
		);
	}
}
