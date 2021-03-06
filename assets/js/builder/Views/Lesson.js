/**
 * Single Lesson View
 * @since    3.16.0
 * @version  3.16.12
 */
define( [
		'Views/_Detachable',
		'Views/_Editable',
		'Views/_Shiftable',
		'Views/_Trashable'
	], function(
		Detachable,
		Editable,
		Shiftable,
		Trashable
	) {

	return Backbone.View.extend( _.defaults( {

		/**
		 * Get default attributes for the html wrapper element
		 * @return   obj
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		attributes: function() {
			return {
				'data-id': this.model.id,
				'data-section-id': this.model.get( 'parent_section' ),
			};
		},

		/**
		 * HTML class names
		 * @type  {String}
		 */
		className: 'llms-builder-item llms-lesson',

		/**
		 * Events
		 * @type  {Object}
		 * @since    3.16.0
		 * @version  3.16.12
		 */
		events: _.defaults( {
			'click .edit-lesson': 'open_lesson_editor',
			'click .edit-quiz': 'open_quiz_editor',
			'click .section-prev': 'section_prev',
			'click .section-next': 'section_next',
			'click .shift-up--lesson': 'shift_up',
			'click .shift-down--lesson': 'shift_down',
		}, Detachable.events, Editable.events, Trashable.events ),

		/**
		 * HTML element wrapper ID attribute
		 * @return   string
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		id: function() {
			return 'llms-lesson-' + this.model.id;
		},

		/**
		 * Wrapper Tag name
		 * @type  {String}
		 */
		tagName: 'li',

		/**
		 * Get the underscore template
		 * @type  {[type]}
		 */
		template: wp.template( 'llms-lesson-template' ),

		/**
		 * Initialization callback func (renders the element on screen)
		 * @return   void
		 * @since    3.14.1
		 * @version  3.14.1
		 */
		initialize: function() {

			this.render();

			this.listenTo( this.model, 'change', this.render );

			Backbone.pubSub.on(  'lesson-selected', this.on_select, this );
			Backbone.pubSub.on(  'new-lesson-added', this.on_select, this );

		},

		/**
		 * Compiles the template and renders the view
		 * @return   self (for chaining)
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		render: function() {

			this.$el.html( this.template( this.model ) );
			this.maybe_hide_shiftable_buttons();
			if ( this.model.get( '_selected' ) ) {
				this.$el.addClass( 'selected' );
			} else {
				this.$el.removeClass( 'selected' );
			}
			return this;

		},

		/**
		 * Click event for lesson settings action icon
		 * Opens sidebar to the lesson editor tab
		 * @return   void
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		open_lesson_editor: function() {

			Backbone.pubSub.trigger( 'lesson-selected', this.model, 'lesson' );
			this.model.set( '_selected', true );

		},

		/**
		 * Click event for the quiz editor action icon
		 * Opens sidebar to the quiz editor tab
		 * @return   void
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		open_quiz_editor: function() {

			Backbone.pubSub.trigger( 'lesson-selected', this.model, 'quiz' );
			this.model.set( '_selected', true );

		},

		/**
		 * When a lesson is selected mark it as selected in the hidden prop
		 * Allows views to re-render and reflect current state properly
		 * @param    obj   model  lesson model that's been selected
		 * @return   void
		 * @since    3.16.0
		 * @version  3.16.0
		 */
		on_select: function( model ) {

			if ( this.model.id !== model.id ) {
				this.model.set( '_selected', false );
			}

		},

		/**
		 * Click event for the "Next Section" button
		 * @param    obj   event   js event obj
		 * @return   void
		 * @since    3.16.11
		 * @version  3.16.11
		 */
		section_next: function( event ) {
			event.preventDefault();
			this._move_to_section( 'next' );
		},

		/**
		 * Click event for the "Previous Section" button
		 * @param    obj   event   js event obj
		 * @return   void
		 * @since    3.16.11
		 * @version  3.16.11
		 */
		section_prev: function( event ) {
			event.preventDefault();
			this._move_to_section( 'prev' );
		},

		/**
		 * Move the lesson into a new section
		 * @param    string   direction  direction [prev|next]
		 * @return   void
		 * @since    3.16.11
		 * @version  3.16.11
		 */
		_move_to_section: function( direction ) {

			var from_coll = this.model.collection,
				to_section;

			if ( 'next' === direction ) {
				to_section = from_coll.parent.get_next();
			} else if ( 'prev' === direction ) {
				to_section = from_coll.parent.get_prev();
			}

			if ( to_section ) {

				from_coll.remove( this.model );
				to_section.add_lesson( this.model );
				to_section.set( '_expanded', true );

			}

		},

	}, Detachable, Editable, Shiftable, Trashable ) );

} );
