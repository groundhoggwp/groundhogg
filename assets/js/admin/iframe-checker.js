let IsFrame = {};

(() => {

    try {
        if ( window.self !== window.top ){

            isFrame.framed = true

            document.body.classList.add( 'iframed' )

        }
    } catch (e) {

    }

})();
