class custom_qr extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                qrcode: '.ccewd_qr_code',
                wallet_address: '.wallet-address-input',
                
            },
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        return {
            $qrcode: this.$element.find(selectors.qrcode),
            $wallet_address: this.$element.find(selectors.wallet_address),

        };
    }

    bindEvents() {
        var qrEle = this.elements.$qrcode;
        var elements = this.elements.$wallet_address;
    
        for (let i = 0; i < qrEle.length; i++) { // Use let instead of var for block scope
            var qrcode = new QRCode(qrEle[i], {
                width: 100,
                height: 100
            });
    
            // Define makeCode inside the loop to capture the current index with a closure
            let makeCode = () => { // Use arrow function to keep this context
                
                if (elements[i].value) {
                    var elText = elements[i].value;
    
                    if (!elText) {
                        console.log("No wallet address");
                        return;
                    }
                    qrcode.makeCode(elText);
                } else {
                    console.log("No wallet address given");
                }
            };
    
            makeCode(); // Call makeCode for the current QR code
    
            // Use closures to ensure the correct index is used
            elements[i].addEventListener("blur", makeCode);
    
            elements[i].addEventListener("keydown", (e) => {
                if (e.keyCode == 13) {
                    makeCode(); // Call makeCode for the current element
                }
            });
        }
    }
}
jQuery(window).on('elementor/frontend/init', () => {

    const addHandler = ($element) => {
        elementorFrontend.elementsHandler.addHandler(custom_qr, {
            $element,
        });
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/cryptocurrency-donation-box-widget.default', addHandler);

});


