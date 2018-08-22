function sticky_admin_sidebars() {
    // Define area to scroll and what it's relative to
    var sidebar_container = '#postbox-container-1';
    var parent_container = '#poststuff';

    // Determine heights
    var sidebar_height = jQuery(sidebar_container).height();
    var parent_height = jQuery(parent_container).height();

    //  Get offsets of document and sidebar starting position
    var document_offset = jQuery(window).scrollTop();
    var initial_offset = jQuery(parent_container).offset().top;

    // Add extra offset when scrolling so looks nicer and set default sidebar offset
    var extra_offset = jQuery('#wpadminbar').height() + 10;
    var sidebar_offset = 0;

    // Work out maximum scroll amount by subtracting the sidebar height from the height of the parent
    var max_scroll = parent_height - sidebar_height;

    // If sidebar height is more than the parent's height or sidebar is not shown on the sidebar, set position to static and stop
    if (parent_height <= sidebar_height || jQuery(window).width() < 850) {
        jQuery(sidebar_container).css('position', 'static');
        return;
    }

    // If we have scrolled past the initial offset
    if (document_offset > initial_offset) {
        // Determine sidebar offset
        sidebar_offset = document_offset - initial_offset + extra_offset;

        // If the sidebar offset is more than max_scroll, set to max_scroll
        if (sidebar_offset > max_scroll) {
            sidebar_offset = max_scroll;
        }
    } else {
        // Sidebar hasn't past initial offset or user has scrolled back to top
        sidebar_offset = 0;
    }

    // Set position to relative and animate into view
    jQuery(sidebar_container).css('position', 'relative').animate({
        top: sidebar_offset
    }, 250, 'swing');
}

// Monitor scroll event
jQuery(window).scroll(function() {
    // Only run positioning function when user stops scrolling
    // Thanks to @yckart - http://stackoverflow.com/a/14092859/236038
    clearTimeout(jQuery.data(this, 'scrollTimer'));
    jQuery.data(this, 'scrollTimer', setTimeout(function() {
        sticky_admin_sidebars();
    }, 250));
});

// If window has resized or loaded for the first time run positioning function
jQuery(window).bind('resize load', function() {
    sticky_admin_sidebars();
});