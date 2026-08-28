<?php
namespace AgencyManager\Forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and handles submission of every `am_form` — the two legacy
 * built-in slugs (talent-application, location-submission) plus any form
 * created in the Form Builder, via the generic ID-based [agency_form]
 * shortcode. Field schema comes from Form_Schema::normalize_fields(); field
 * types from Field_Types. Nonce + honeypot, same shape as before.
 */
class Form_Renderer {

	private const NONCE_ACTION     = 'am_submit_form';
	private const MAX_UPLOAD_BYTES = 10 * MB_IN_BYTES;

	public function register(): void {
		add_shortcode( 'talent_application_form', array( $this, 'render_talent_application' ) );
		add_shortcode( 'location_submission_form', array( $this, 'render_location_submission' ) );
		add_shortcode( 'agency_form', array( $this, 'render_agency_form_shortcode' ) );

		add_action( 'admin_post_am_submit_form', array( $this, 'handle_submit' ) );
		add_action( 'admin_post_nopriv_am_submit_form', array( $this, 'handle_submit' ) );
	}

	public function render_talent_application(): string {
		return $this->render_form( 'talent-application' );
	}

	public function render_location_submission(): string {
		return $this->render_form( 'location-submission' );
	}

	/**
	 * `[agency_form id="123"]` — the generic, ID-based shortcode any form
	 * created in the Form Builder automatically gets, so a brand-new custom
	 * form needs no shortcode or widget written by hand.
	 *
	 * @param array|string $atts
	 */
	public function render_agency_form_shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'agency_form' );
		$id   = absint( $atts['id'] );

		return $id ? $this->render_form_by_id( $id ) : '';
	}

	public function render_form_by_id( int $form_id, array $hidden_fields = array() ): string {
		$form = get_post( $form_id );
		if ( ! $form || 'am_form' !== $form->post_type ) {
			return '';
		}

		return $this->render_form_post( $form, $hidden_fields );
	}

	/**
	 * Public so both the shortcode callbacks above and the Elementor form
	 * widgets (which additionally support hiding specific fields per
	 * instance) share this one rendering path.
	 */
	public function render_form( string $slug, array $hidden_fields = array() ): string {
		$form = $this->get_form_by_slug( $slug );
		if ( ! $form ) {
			return '';
		}

		return $this->render_form_post( $form, $hidden_fields );
	}

	private function render_form_post( \WP_Post $form, array $hidden_fields ): string {
		$all_fields = $this->get_fields( $form->ID );
		$fields     = array_filter(
			$all_fields,
			static function ( $field ) use ( $hidden_fields ) {
				return ! in_array( $field['key'], $hidden_fields, true );
			}
		);

		$sent  = isset( $_GET['am_sent'] ) && '1' === $_GET['am_sent'] && isset( $_GET['am_form'] ) && (int) $_GET['am_form'] === $form->ID;
		$error = isset( $_GET['am_error'] ) ? sanitize_key( wp_unslash( $_GET['am_error'] ) ) : '';

		// Image/file fields render as a plain <input type="file"> here (see
		// render_field() below) — no JS picker needed, so this deliberately
		// does not enqueue wp.media or admin.js. Those are for the
		// wp-admin meta-box/term-meta UI only (see Meta_Boxes, Term_Meta),
		// which target .am-media-picker markup this template never outputs.
		wp_enqueue_style( 'am-frontend' );
		wp_enqueue_script( 'am-form-conditional' );

		ob_start();

		if ( $sent ) {
			$confirmation = $this->get_confirmation_message( $form->ID );
			echo '<div class="am-form-notice am-form-notice--success">' . esc_html( $confirmation ) . '</div>';
			return (string) ob_get_clean();
		}
		?>
		<form class="am-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="am_submit_form">
			<input type="hidden" name="am_form_id" value="<?php echo esc_attr( $form->ID ); ?>">
			<?php if ( $hidden_fields ) : ?>
				<input type="hidden" name="am_hidden_fields" value="<?php echo esc_attr( implode( ',', $hidden_fields ) ); ?>">
			<?php endif; ?>
			<?php wp_nonce_field( self::NONCE_ACTION, 'am_form_nonce' ); ?>

			<?php if ( 'invalid' === $error ) : ?>
				<div class="am-form-notice am-form-notice--error"><?php esc_html_e( 'Please fill in all required fields and try again.', 'agency-manager' ); ?></div>
			<?php endif; ?>

			<?php foreach ( $fields as $field ) : ?>
				<?php $this->render_field( $field, $all_fields ); ?>
			<?php endforeach; ?>

			<p class="am-form-honeypot" aria-hidden="true">
				<label for="am_company">Company</label>
				<input type="text" id="am_company" name="am_company" tabindex="-1" autocomplete="off">
			</p>

			<button type="submit" class="am-btn"><?php esc_html_e( 'Submit', 'agency-manager' ); ?></button>
		</form>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * @param array $all_fields Every field on this form (not just visible ones) — needed to resolve a conditional's source field by id.
	 */
	private function render_field( array $field, array $all_fields ): void {
		$type = $field['type'];

		if ( 'hidden' === $type ) {
			printf( '<input type="hidden" name="am_field_%1$s" value="%2$s">', esc_attr( $field['key'] ), esc_attr( $field['default'] ) );
			return;
		}

		$condition_attr = $this->conditional_attribute( $field, $all_fields );

		if ( in_array( $type, array( 'html', 'heading', 'divider' ), true ) ) {
			printf( '<div class="am-form-row am-form-row--%1$s"%2$s>', esc_attr( $type ), $condition_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- conditional_attribute() escapes internally.
			if ( 'heading' === $type ) {
				echo '<h3>' . esc_html( $field['label'] ) . '</h3>';
			} elseif ( 'html' === $type ) {
				echo wp_kses_post( $field['default'] );
			}
			echo '</div>';
			return;
		}

		$key      = $field['key'];
		$name     = 'am_field_' . $key;
		$required = ! empty( $field['required'] );
		$class    = trim( 'am-form-row ' . ( $field['css_class'] ?? '' ) );
		?>
		<div class="<?php echo esc_attr( $class ); ?>"<?php echo $condition_attr; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>>
			<label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' <span class="am-required" aria-hidden="true">*</span>' : ''; ?></label>
			<?php if ( ! empty( $field['description'] ) ) : ?>
				<p class="am-form-row__description"><?php echo esc_html( $field['description'] ); ?></p>
			<?php endif; ?>
			<?php $this->render_input( $field, $name, $required ); ?>
		</div>
		<?php
	}

	private function render_input( array $field, string $name, bool $required ): void {
		$type        = $field['type'];
		$placeholder = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
		$default     = (string) ( $field['default'] ?? '' );
		$req_attr    = $required ? ' required aria-required="true"' : '';
		$len_attrs   = '';
		if ( ! empty( $field['min_length'] ) ) {
			$len_attrs .= ' minlength="' . (int) $field['min_length'] . '"';
		}
		if ( ! empty( $field['max_length'] ) ) {
			$len_attrs .= ' maxlength="' . (int) $field['max_length'] . '"';
		}

		switch ( $type ) {
			case 'textarea':
				echo '<textarea id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" rows="4"' . $placeholder . $req_attr . $len_attrs . '>' . esc_textarea( $default ) . '</textarea>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component parts already escaped above.
				break;

			case 'select':
				echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $req_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				if ( ! $required ) {
					echo '<option value=""></option>';
				}
				foreach ( $field['options'] as $option ) {
					echo '<option value="' . esc_attr( $option['value'] ) . '"' . selected( $default, $option['value'], false ) . '>' . esc_html( $option['label'] ) . '</option>';
				}
				echo '</select>';
				break;

			case 'multiselect':
				echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '[]" multiple' . $req_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				foreach ( $field['options'] as $option ) {
					echo '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
				}
				echo '</select>';
				break;

			case 'radio':
				foreach ( $field['options'] as $i => $option ) {
					$id = $name . '_' . $i;
					echo '<label class="am-form-choice"><input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $option['value'] ) . '"' . checked( $default, $option['value'], false ) . ( 0 === $i ? $req_attr : '' ) . '> ' . esc_html( $option['label'] ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				break;

			case 'checkbox_group':
				foreach ( $field['options'] as $i => $option ) {
					$id = $name . '_' . $i;
					echo '<label class="am-form-choice"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $option['value'] ) . '"> ' . esc_html( $option['label'] ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				break;

			case 'checkbox':
				echo '<label class="am-form-choice"><input type="checkbox" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '1', $default, false ) . $req_attr . '> ' . esc_html( $field['label'] ) . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'file':
			case 'image':
				echo '<input type="file" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $req_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'files':
			case 'gallery':
				echo '<input type="file" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '[]" multiple' . $req_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'number':
				echo '<input type="number" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'password':
				echo '<input type="password" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'date':
				echo '<input type="date" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'time':
				echo '<input type="time" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'datetime':
				echo '<input type="datetime-local" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'email':
				echo '<input type="email" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'tel':
				echo '<input type="tel" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			case 'url':
				echo '<input type="url" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				break;

			default:
				echo '<input type="text" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"' . $placeholder . $req_attr . $len_attrs . ' value="' . esc_attr( $default ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * @param array $all_fields Every field on the form, used to resolve `conditional.field_id` to that field's `key` (the frontend script matches on input name, not id).
	 */
	private function conditional_attribute( array $field, array $all_fields ): string {
		$conditional = $field['conditional'] ?? null;
		if ( ! is_array( $conditional ) || empty( $conditional['field_id'] ) ) {
			return '';
		}

		$source = null;
		foreach ( $all_fields as $candidate ) {
			if ( $candidate['id'] === $conditional['field_id'] ) {
				$source = $candidate;
				break;
			}
		}

		if ( ! $source ) {
			return '';
		}

		$payload = wp_json_encode(
			array(
				'field'    => 'am_field_' . $source['key'],
				'operator' => in_array( $conditional['operator'] ?? '', array( 'is', 'is_not' ), true ) ? $conditional['operator'] : 'is',
				'value'    => (string) ( $conditional['value'] ?? '' ),
			)
		);

		return ' data-am-conditional="' . esc_attr( (string) $payload ) . '"';
	}

	public function handle_submit(): void {
		$redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );

		if ( ! isset( $_POST['am_form_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['am_form_nonce'] ) ), self::NONCE_ACTION ) ) {
			wp_safe_redirect( add_query_arg( 'am_error', 'invalid', $redirect ) );
			exit;
		}

		// Honeypot: bots fill hidden fields, humans never see this one.
		if ( ! empty( $_POST['am_company'] ) ) {
			wp_safe_redirect( remove_query_arg( 'am_error', add_query_arg( 'am_sent', '1', $redirect ) ) );
			exit;
		}

		$form_id = isset( $_POST['am_form_id'] ) ? absint( $_POST['am_form_id'] ) : 0;
		$form    = $form_id ? get_post( $form_id ) : null;

		if ( ! $form || 'am_form' !== $form->post_type ) {
			wp_safe_redirect( add_query_arg( 'am_error', 'invalid', $redirect ) );
			exit;
		}

		/**
		 * Lets an integration reject a submission before it's stored (e.g. a
		 * rate-limiter or third-party spam check). Return false (or a
		 * WP_Error) to block; nothing hooks this by default.
		 *
		 * @param bool  $allowed
		 * @param int   $form_id
		 * @param array $post_data Raw, unsanitized $_POST (read-only use only).
		 */
		$allowed = apply_filters( 'am_form_submission_allowed', true, $form_id, $_POST );
		if ( ! $allowed || is_wp_error( $allowed ) ) {
			wp_safe_redirect( add_query_arg( 'am_error', 'invalid', $redirect ) );
			exit;
		}

		$fields = $this->get_fields( $form_id );

		// Fields an Elementor widget instance chose to hide are exempt from
		// required-field validation here too — the visitor never saw them.
		$hidden_fields = isset( $_POST['am_hidden_fields'] )
			? array_filter( array_map( 'sanitize_key', explode( ',', wp_unslash( $_POST['am_hidden_fields'] ) ) ) )
			: array();

		// Pass 1: sanitize every visible field's raw value, before enforcing
		// required-ness — a field's conditional may depend on any other
		// field regardless of order, so required-checks (pass 2) need every
		// value resolved first.
		$raw_values = array();
		foreach ( $fields as $field ) {
			if ( in_array( $field['key'], $hidden_fields, true ) || Field_Types::is_non_input_type( $field['type'] ) ) {
				continue;
			}
			$raw_values[ $field['key'] ] = $this->sanitize_field_value( $field );
		}

		$values = array();
		foreach ( $fields as $field ) {
			if ( in_array( $field['key'], $hidden_fields, true ) || Field_Types::is_non_input_type( $field['type'] ) ) {
				continue;
			}

			// A field hidden by its own conditional logic was never shown to
			// this visitor — never required, never stored, exactly as if it
			// didn't exist on the form for this submission.
			if ( ! $this->condition_met( $field, $fields, $raw_values ) ) {
				continue;
			}

			$value = $raw_values[ $field['key'] ];

			if ( ! empty( $field['required'] ) && $this->is_empty_value( $value ) ) {
				wp_safe_redirect( add_query_arg( 'am_error', 'invalid', $redirect ) );
				exit;
			}

			$values[ $field['key'] ] = $value;
		}

		$type = get_post_meta( $form_id, '_am_form_type', true );

		$submission_id = wp_insert_post(
			array(
				'post_type'   => 'am_submission',
				'post_title'  => $values['full_name'] ?? ( $values['location_name'] ?? __( 'Submission', 'agency-manager' ) ),
				'post_status' => 'publish',
			)
		);

		if ( $submission_id && ! is_wp_error( $submission_id ) ) {
			update_post_meta( $submission_id, '_am_form_id', $form_id );
			update_post_meta( $submission_id, '_am_type', $type ? $type : 'talent' );
			update_post_meta( $submission_id, '_am_status', 'submitted' );
			update_post_meta( $submission_id, '_am_field_values', wp_json_encode( $values ) );

			if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
				update_post_meta( $submission_id, '_am_submitter_ip', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) );
			}

			/**
			 * Fires after a new submission is stored. Notifications listens
			 * here rather than being called directly, so this class stays
			 * unaware of how (or whether) notifications are delivered.
			 */
			do_action( 'am_submission_created', $submission_id );
		}

		wp_safe_redirect( remove_query_arg( 'am_error', add_query_arg( array( 'am_sent' => '1', 'am_form' => $form_id ), $redirect ) ) );
		exit;
	}

	/**
	 * @param array $raw_values Sanitized values keyed by field `key`, for every visible non-content field.
	 */
	private function condition_met( array $field, array $all_fields, array $raw_values ): bool {
		$conditional = $field['conditional'] ?? null;
		if ( ! is_array( $conditional ) || empty( $conditional['field_id'] ) ) {
			return true;
		}

		$source_key = null;
		foreach ( $all_fields as $candidate ) {
			if ( $candidate['id'] === $conditional['field_id'] ) {
				$source_key = $candidate['key'];
				break;
			}
		}

		// The referenced field no longer exists (e.g. deleted after the
		// condition was set) — fail open rather than silently hide the field.
		if ( null === $source_key || ! array_key_exists( $source_key, $raw_values ) ) {
			return true;
		}

		$actual   = $raw_values[ $source_key ];
		$expected = (string) ( $conditional['value'] ?? '' );
		$is       = is_array( $actual ) ? in_array( $expected, array_map( 'strval', $actual ), true ) : ( (string) $actual === $expected );

		return 'is_not' === ( $conditional['operator'] ?? 'is' ) ? ! $is : $is;
	}

	/**
	 * @param mixed $value
	 */
	private function is_empty_value( $value ): bool {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		return '' === $value || null === $value;
	}

	/**
	 * @return string|int|array
	 */
	private function sanitize_field_value( array $field ) {
		$name = 'am_field_' . $field['key'];
		$type = $field['type'];

		if ( Field_Types::is_file_type( $type ) ) {
			return $this->sanitize_file_value( $field, $name );
		}

		if ( in_array( $type, array( 'multiselect', 'checkbox_group' ), true ) ) {
			if ( empty( $_POST[ $name ] ) || ! is_array( $_POST[ $name ] ) ) {
				return array();
			}
			$allowed = wp_list_pluck( $field['options'], 'value' );
			$raw     = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $name ] ) );
			return $allowed ? array_values( array_intersect( $raw, $allowed ) ) : array_values( $raw );
		}

		if ( ! isset( $_POST[ $name ] ) ) {
			return 'checkbox' === $type ? 0 : '';
		}

		$raw = wp_unslash( $_POST[ $name ] );
		if ( is_array( $raw ) ) {
			$raw = '';
		}

		switch ( $type ) {
			case 'email':
				$value = sanitize_email( $raw );
				return is_email( $value ) ? $value : '';
			case 'url':
				return esc_url_raw( $raw );
			case 'textarea':
				return sanitize_textarea_field( $raw );
			case 'checkbox':
				return $raw ? 1 : 0;
			case 'number':
				return is_numeric( $raw ) ? $raw + 0 : '';
			case 'select':
			case 'radio':
				$allowed = wp_list_pluck( $field['options'], 'value' );
				$value   = sanitize_text_field( $raw );
				return ( ! $allowed || in_array( $value, $allowed, true ) ) ? $value : '';
			case 'date':
				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
			case 'time':
				return preg_match( '/^\d{2}:\d{2}(:\d{2})?$/', $raw ) ? $raw : '';
			case 'datetime':
				return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $raw ) ? sanitize_text_field( $raw ) : '';
			default:
				$value = sanitize_text_field( $raw );
				if ( ! empty( $field['max_length'] ) ) {
					$value = mb_substr( $value, 0, (int) $field['max_length'] );
				}
				return $value;
		}
	}

	/**
	 * @return int|array<int,int> A single attachment ID for file/image, an array of them for files/gallery.
	 */
	private function sanitize_file_value( array $field, string $name ) {
		$multiple = Field_Types::is_multi_file_type( $field['type'] );
		$key      = $multiple ? $name : $name;

		if ( $multiple ) {
			if ( empty( $_FILES[ $name ]['name'] ) || ! is_array( $_FILES[ $name ]['name'] ) ) {
				return array();
			}
			$count      = min( count( $_FILES[ $name ]['name'] ), ! empty( $field['max_files'] ) ? (int) $field['max_files'] : 10 );
			$ids        = array();
			$restrict   = 'gallery' === $field['type'];
			for ( $i = 0; $i < $count; $i++ ) {
				if ( empty( $_FILES[ $name ]['name'][ $i ] ) ) {
					continue;
				}
				$single_file = array(
					'name'     => $_FILES[ $name ]['name'][ $i ],
					'type'     => $_FILES[ $name ]['type'][ $i ],
					'tmp_name' => $_FILES[ $name ]['tmp_name'][ $i ],
					'error'    => $_FILES[ $name ]['error'][ $i ],
					'size'     => $_FILES[ $name ]['size'][ $i ],
				);
				if ( ! empty( $single_file['size'] ) && $single_file['size'] > $this->max_bytes( $field ) ) {
					continue;
				}
				$id = $this->upload_single( $single_file, $restrict );
				if ( $id ) {
					$ids[] = $id;
				}
			}
			return $ids;
		}

		if ( empty( $_FILES[ $key ]['name'] ) ) {
			return '';
		}

		if ( ! empty( $_FILES[ $key ]['size'] ) && $_FILES[ $key ]['size'] > $this->max_bytes( $field ) ) {
			return '';
		}

		$id = $this->upload_single( $_FILES[ $key ], 'image' === $field['type'] );

		return $id ? $id : '';
	}

	private function max_bytes( array $field ): int {
		return ! empty( $field['max_file_size'] ) ? ( (int) $field['max_file_size'] * MB_IN_BYTES ) : self::MAX_UPLOAD_BYTES;
	}

	/**
	 * @param array $file One $_FILES-shaped entry.
	 */
	private function upload_single( array $file, bool $restrict_to_images ): int {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		if ( $restrict_to_images ) {
			add_filter( 'upload_mimes', array( $this, 'restrict_to_image_mimes' ) );
		}

		// media_handle_sideload() operates on an already-populated $_FILES-
		// shaped array without requiring the field name to exist verbatim in
		// the superglobal — used here so multi-file fields (where each file
		// was split out of a nested $_FILES array above) upload the same way
		// single-file fields do via media_handle_upload().
		$overrides     = array( 'test_form' => false );
		$uploaded      = wp_handle_upload( $file, $overrides );
		$attachment_id = 0;

		if ( empty( $uploaded['error'] ) && ! empty( $uploaded['file'] ) ) {
			$attachment_id = media_handle_sideload(
				array(
					'name'     => $file['name'],
					'tmp_name' => $uploaded['file'],
				),
				0
			);
			$attachment_id = is_wp_error( $attachment_id ) ? 0 : (int) $attachment_id;
		}

		if ( $restrict_to_images ) {
			remove_filter( 'upload_mimes', array( $this, 'restrict_to_image_mimes' ) );
		}

		return $attachment_id;
	}

	/**
	 * Narrows the allowed upload mime types to actual images for "image"
	 * field uploads, rather than WordPress's full default allowed list.
	 */
	public function restrict_to_image_mimes( array $mimes ): array {
		return array_intersect_key(
			$mimes,
			array_flip( array( 'jpg|jpeg|jpe', 'gif', 'png', 'webp' ) )
		);
	}

	private function get_form_by_slug( string $slug ): ?\WP_Post {
		$posts = get_posts(
			array(
				'post_type'   => 'am_form',
				'name'        => $slug,
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		return $posts ? $posts[0] : null;
	}

	public function get_fields( int $form_id ): array {
		$raw    = get_post_meta( $form_id, '_am_form_fields', true );
		$fields = $raw ? json_decode( $raw, true ) : array();

		if ( ! is_array( $fields ) ) {
			$fields = array();
		}

		$fields = Form_Schema::normalize_fields( $fields );

		/**
		 * Filters a form's field schema before it's rendered or used for
		 * submission validation. Lets other code add, remove, or adjust
		 * fields for a given form without editing it via wp-admin.
		 *
		 * @param array $fields  Normalized field definitions.
		 * @param int   $form_id The `am_form` post ID.
		 */
		return apply_filters( 'am_form_fields', $fields, $form_id );
	}

	private function get_confirmation_message( int $form_id ): string {
		$message = get_post_meta( $form_id, '_am_form_confirmation_message', true );

		return $message ? (string) $message : __( 'Thank you — your submission has been received.', 'agency-manager' );
	}
}
