/**
 * @license
 * jquery-ccmask
 * Simple jQuery Credit Card Masking Plugin
 * Copyright Drew Foehn <drew@pixelburn.net> All Rights Reserved
 * License MIT
 */

(function(root, factory) {
    "use strict";
    if(typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else {
        factory(root.jQuery);
    }
}(this, function($) {

    "use strict";

    var defaultOptions = {
        blur: true, // masks on blur, if false masks on keyup
        keyup: false
    };

    /**
     * returns the keys of a collection
     * @param o
     * @returns {Array}
     */
    function getKeys(o) {
        if(Object.keys) {
            return Object.keys(o);
        }
        if(o !== Object(o)) {
            throw new TypeError('Object.keys called on a non-object');
        }
        var k = [], p;
        for(p in o) {
            if(Object.prototype.hasOwnProperty.call(o, p)) {
                k.push(p);
            }
        }
        return k;
    }

    /**
     * Registers event bindings for input fields
     * @param ele
     * @param eventCollection
     * @param options
     */
    function registerEvents(ele, eventCollection, options) {
        var events = getKeys(eventCollection), e;
        for(var i = 0; i < events.length; i++) {
            e = events[i];

            if(e === 'submit') {
                $(ele).parents('form').on(e, {ccmask: ele}, eventCollection[e]);
            } else {
                $(ele).on(e, eventCollection[e]);
            }
        }
    }

    /**
     * de registers events for input fields
     * @param ele
     * @param eventCollection
     */
    function deRegisterEvents(ele, eventCollection) {
        var events = getKeys(eventCollection), e;
        for(var i = 0; i < events.length; i++) {
            e = events[i];

            if(e === 'submit') {
                $(ele).parents('form').off(e, {ccmask: ele}, eventCollection[e]);
            } else {
                $(ele).off(e, eventCollection[e]);
            }
        }
    }

    /**
     * Returns formatting information as an array
     * @param cardNumber
     * @returns {number[]}
     */
    function getCardFormat (cardNumber) {

        // default 16 digit
        var cardFormat = [4, 4, 4, 4];

        // amex 15
        if(/^(3[47])/.test(cardNumber)) {
            cardFormat = [4, 6, 5];
        }

        // diners international 14
        if(/^(3(0[0123459]|[689]))/.test(cardNumber)) {
            cardFormat = [4, 6, 4];
        }

        return cardFormat
    }

    /**
     * Formats the card based on format rules returned by getCardFormat
     * @param cardnumber
     * @param format
     * @returns {string}
     */
    function format(cardnumber, format) {

        format = format || [4,4,4,4];

        var cn = cardnumber.split(''), val = [], i = 0, currNo = 0;

        do {
            val = val.concat(cn.slice(currNo, currNo + format[i]));
            val.push(' ');
            currNo += format[i];
        } while(i++ < format.length);

        return val.join('').replace(/\s\s*$/, '');
    }

    /**
     * Replaces all but the last 4 digits as •
     * @param val
     * @returns {string}
     */
    function mask(val) {
        var ret = new Array(val.length - (val.slice(-3).length));
        return ret.join('•') + val.slice(-4);
    }

    /* The following to functions were
     * lifted from Mitchell Simoens @SenchaMitch
     * http://www.sencha.com/forum/showthread.php?95486-Cursor-Position-in-TextField
     */
    /**
     * @param ele
     * @returns {number}
     */
    function getCaretPos(ele) {
        var rng, ii=-1;
        if(typeof ele.selectionStart=='number') {
            ii=ele.selectionStart;
        } else if (document.selection && ele.createTextRange){
            rng=document.selection.createRange();
            rng.collapse(true);
            rng.moveStart('character', -ele.value.length);
            ii=rng.text.length;
        }
        return ii;
    }

    function setCaretTo(ele, pos) {
        if(ele.createTextRange) {
            var range = ele.createTextRange();
            range.move('character', pos);
            range.select();
        } else if(ele.selectionStart) {
            ele.focus();
            ele.setSelectionRange(pos, pos);
        }
    }

    /**
     * Moves caret to end of field
     * @param ele
     */
    function caretEnd(ele) {
        if(ele.setSelectionRange) {
            setCaretTo(ele, ele.value.length);
        } else {
            ele.value = ele.value;
        }
    }

    var oldHooks = $.valHooks.input;

    // overrite jquery valhooks object non destructively
    $.valHooks.text = {
        get: function(ele) {
            var value = ele.value;

            if(oldHooks) {
                value = oldHooks.get.apply(this, arguments);
            }

            if($(ele).data('ccmask')) {
                value = $(ele).data('unmaskedValue');
            }

            return value;
        },
        set: function(ele, value) {

            if($(ele).data('ccmask')) {
                value = value.replace(/[^0-9]+/, '').substring(0, 16);
                $(ele).data('unmaskedValue', value);
            }

            if(oldHooks) {
                value = oldHooks.set.apply(this, arguments);
            }

            return value;
        }
    };

    var ccmask = {};

    ccmask.onBlur = {

        keypress: function(e) {
            var value = String.fromCharCode(e.charCode || e.which);

            if(!/([0-9])/.test(value) || this.value.length > 16) {
                e.preventDefault();
            }
        },

        keyup: function() {
            $(this).val($(this).prop('value'));
        },

        blur: function(e) {
            var value = $(this).data('unmaskedValue'),
                maskedValue = format(mask(value), getCardFormat(value));

            if(!$(this).hasClass('placeholder')) {
                $(this).prop('value', maskedValue);
                $(this).data('maskedValue' , maskedValue);
            }
        },

        focus: function() {
            $(this).prop('value', $(this).data('unmaskedValue'));
        },
        submit: function(e) {
            var ele = e.data.ccmask;

            if($(this).data('validator')) {
                if(!$(this).data('validator').element(ele)) {
                    return false;
                }
            }

            $(ele).prop('value', $(ele).data('unmaskedValue'));
        }
    };

    ccmask.onKeyUp = {
        mouseup: function() {
            caretEnd(this);
        },
        keydown: function(e) {
            // on delete
            if(e.keyCode === 8) {
                $(this).val($(this).val().substring(0, $(this).val().length - 1));
                $(this).prop('value', format(mask($(this).val()), getCardFormat($(this).val())));
                e.preventDefault();
            }
        },
        keypress: function(e) {
            var value = String.fromCharCode(e.charCode || e.which);

            if(this.value === '') {
                $(this).val('');
            }

            if(/([0-9])/.test(value) && $(this).val().length < 16) {

                if($(this).val() !== '') {
                    value = $(this).val() + value;
                }
                if(value !== '') {
                    $(this).val(value);
                    $(this).prop('value', format(mask(value)));
                }
            }
            e.preventDefault();

        },
        keyup: function() {
            if(this.value === '') {
                $(this).val('');
            }
            caretEnd(this);
        },
        blur: function() {
            if(this.value === '') {
                $(this).val('');
            }
        },
        paste: function() {
            var ele = this;
            ele.value = '';
            $(ele).val('');

            setTimeout(function() {
                var value = ele.value.replace(/[^0-9]+/, '').substring(0, 16);
                $(ele).val(value);
                $(ele).prop('value', format(mask($(ele).val()), getCardFormat($(ele).val())));
            }, 100);
        },
        submit: function(e) {
            var ele = e.data.ccmask;

            if($(this).data('validator')) {
                if(!$(this).data('validator').element(ele)) {
                    return false;
                }
            }

            $(ele).prop('value', $(ele).data('unmaskedValue'));
        }

    };

    $.fn.ccmask = function(options) {

        if(!options) {
            options = $.extend(defaultOptions);;
        }

        return this.each(function() {

            if($(this).data('ccmask')) {

                $(this).removeData('ccmask');
                $(this).prop('value', $(this).data('unmaskedValue'));
                $(this).removeData('unmaskedValue');

                if(options.blur) {
                    deRegisterEvents(this, ccmask.onBlur);
                } else {

                    if(options.keyup) {
                        deRegisterEvents(this, ccmask.onKeyUp);
                    }
                }

            } else {

                $(this).data('unmaskedValue', this.value);
                $(this).data('ccmask', true);
                $(this).val($(this).data('unmaskedValue'));

                if(options.blur) {
                    registerEvents(this, ccmask.onBlur);
                } else {

                    if(options.keyup) {
                        registerEvents(this, ccmask.onKeyUp);
                    }
                }

            }

        });
    };

    return $.fn.ccmask;

}));