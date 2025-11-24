(function (wp, acf, $) {
    if ( ! wp || ! acf || ! wp.element || ! $ ) {
        return;
    }

    const { createElement: el, Fragment, useEffect, useState } = wp.element;
    const { TextControl, TextareaControl, Button } = wp.components || {};

    if ( ! TextControl || ! TextareaControl || ! Button ) {
        return;
    }

    const SUB_KEYS = {
        review_body: 'field_kelsie_review_body',
        review_title: 'field_kelsie_review_title',
        reviewer_name: 'field_kelsie_reviewer_name',
        rating_number: 'field_kelsie_rating_number',
        review_id: 'field_kelsie_review_id',
        review_original_location: 'field_kelsie_review_original_location',
    };

    const REPEATER_KEY = 'field_kelsie_client_testimonials';

    const readRows = ( repeater ) => {
        if ( ! repeater || ! repeater.$rows ) {
            return [];
        }

        const rows = [];

        repeater.$rows.each( function ( index ) {
            const $row = $( this );

            const getVal = ( key ) => {
                const sub = acf.getField( SUB_KEYS[ key ], $row );
                return sub && typeof sub.val === 'function' ? sub.val() : '';
            };

            const getRating = () => {
                const sub = acf.getField( SUB_KEYS.rating_number, $row );
                if ( sub && typeof sub.val === 'function' ) {
                    const num = parseFloat( sub.val() );
                    return Number.isFinite( num ) ? num : '';
                }
                return '';
            };

            rows.push( {
                index,
                reviewer_name: getVal( 'reviewer_name' ),
                review_body: getVal( 'review_body' ),
                review_title: getVal( 'review_title' ),
                review_id: getVal( 'review_id' ),
                review_original_location: getVal( 'review_original_location' ),
                rating_number: getRating(),
            } );
        } );

        return rows;
    };

    const updateSubField = ( repeater, rowIndex, subKey, value ) => {
        if ( ! repeater || ! repeater.$rows || ! SUB_KEYS[ subKey ] ) {
            return;
        }

        const $row = repeater.$rows.eq( rowIndex );
        if ( ! $row || ! $row.length ) {
            return;
        }

        const field = acf.getField( SUB_KEYS[ subKey ], $row );
        if ( field && typeof field.val === 'function' ) {
            field.val( value );
            field.$input && field.$input.trigger( 'change' );
        }
    };

    const TestimonialEditor = ( { repeater } ) => {
        const [ rows, setRows ] = useState( () => readRows( repeater ) );

        useEffect( () => {
            if ( ! repeater ) {
                return () => {};
            }

            const sync = () => setRows( readRows( repeater ) );

            sync();

            const events = 'change append remove sortstop';
            repeater.on( events, sync );

            return () => repeater.off( events, sync );
        }, [ repeater ] );

        const addRow = () => {
            if ( repeater && typeof repeater.add === 'function' ) {
                repeater.add();
                setRows( readRows( repeater ) );
            }
        };

        const onChange = ( rowIndex, key, value ) => {
            updateSubField( repeater, rowIndex, key, value );
            setRows( readRows( repeater ) );
        };

        const body = rows.length
            ? rows.map( ( row ) => {
                const reviewId = row.review_id || `${ row.reviewer_name || 'review' }-${ row.index + 1 }`;
                const ratingSafe = Number.isFinite( row.rating_number ) ? row.rating_number : '';

                return el(
                    'article',
                    {
                        key: `kelsie-review-${ row.index }`,
                        className: 'kelsie-review-block__item',
                    },
                    el(
                        'blockquote',
                        { className: 'wp-block-pullquote is-style-solid-color' },
                        el( TextControl, {
                            label: wp.i18n.__( 'Review Title', 'kelsie' ),
                            value: row.review_title,
                            onChange: ( value ) => onChange( row.index, 'review_title', value ),
                            className: 'kelsie-review-block__field',
                        } ),
                        el( TextareaControl, {
                            label: wp.i18n.__( 'Review Body', 'kelsie' ),
                            value: row.review_body,
                            onChange: ( value ) => onChange( row.index, 'review_body', value ),
                            className: 'kelsie-review-block__field',
                        } ),
                        el(
                            'cite',
                            null,
                            el( TextControl, {
                                label: wp.i18n.__( 'Reviewer Name', 'kelsie' ),
                                value: row.reviewer_name,
                                onChange: ( value ) => onChange( row.index, 'reviewer_name', value ),
                                className: 'kelsie-review-block__field',
                            } ),
                            el( TextControl, {
                                label: wp.i18n.__( 'Rating (1-5)', 'kelsie' ),
                                value: ratingSafe,
                                type: 'number',
                                onChange: ( value ) => onChange( row.index, 'rating_number', value ),
                                min: 0,
                                max: 5,
                                step: 1,
                                className: 'kelsie-review-block__field',
                            } ),
                            el( TextControl, {
                                label: wp.i18n.__( 'Review ID (optional)', 'kelsie' ),
                                value: row.review_id,
                                onChange: ( value ) => onChange( row.index, 'review_id', value ),
                                className: 'kelsie-review-block__field',
                                help: wp.i18n.__( 'Used to keep schema anchors stable.', 'kelsie' ),
                            } ),
                            el( TextControl, {
                                label: wp.i18n.__( 'Original Review URL', 'kelsie' ),
                                value: row.review_original_location,
                                type: 'url',
                                onChange: ( value ) => onChange( row.index, 'review_original_location', value ),
                                className: 'kelsie-review-block__field',
                            } ),
                        ),
                        el(
                            'p',
                            { className: 'kelsie-review-block__inline-tip' },
                            wp.i18n.__( 'Drag rows via the Repeater toolbar to reorder.', 'kelsie' )
                        )
                    )
                );
            } )
            : el(
                'p',
                { className: 'kelsie-review-block__notice' },
                wp.i18n.__( 'Add testimonials with the button below. Fields save to ACF.', 'kelsie' )
            );

        return el(
            Fragment,
            null,
            el(
                'div',
                { className: 'kelsie-review-block__editor-list' },
                body
            ),
            el( Button, {
                variant: 'primary',
                icon: 'plus',
                onClick: addRow,
                className: 'kelsie-review-block__add',
            }, wp.i18n.__( 'Add Testimonial', 'kelsie' ) )
        );
    };

    const mountEditor = ( block ) => {
        if ( ! block || ! block.$el ) {
            return;
        }

        const repeater = acf.getField( REPEATER_KEY, block.$el );
        if ( ! repeater ) {
            return;
        }

        const $target = block.$el.find( '.acf-block-preview' );
        if ( ! $target.length ) {
            return;
        }

        let $holder = $target.find( '.kelsie-review-block__editor' );
        if ( ! $holder.length ) {
            $holder = $( '<div class="kelsie-review-block__editor" />' );
            $target.append( $holder );
        }

        wp.element.render( el( TestimonialEditor, { repeater } ), $holder[ 0 ] );
    };

    acf.addAction( 'render_block_preview', function ( block ) {
        if ( block && block.data && block.data.name === 'acf/kelsiecakes-review-list' ) {
            mountEditor( block );
        }
    } );

})( window.wp, window.acf, window.jQuery );
