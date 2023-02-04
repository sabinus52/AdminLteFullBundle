/**
 * Fonction de surcharge d'initialisation des objets "Select2"
 * 
 * @author Sabinus52 <sabinus52@gmail.com>
 * @param json options 
 * @returns 
 */
$.fn.OlixSelect2 = function (options) {
    this.each(function () {

        let $elt = $(this);
        let optionsResult;

        // Options de base
        optionsResult = $.extend(true, options || {}, $elt.data("options-js"));

        // Options pour l'auto completion en AJAX
        let optionsAjax = {};
        if ($elt.data('ajax')) {
            let opts = $elt.data("ajax");
            optionsAjax = {
                ajax: {
                    url: opts.route,
                    dataType: 'json',
                    delay: opts.delay,
                    cache: opts.cache,
                    data: function (params) {
                        let parameter = {};
                        parameter['term'] = params.term;
                        if (opts.scroll) {
                            parameter['page'] = params.page || 1;
                        }
                        return parameter;
                    },
                    processResults: function (data, params) {
                        let results, more = false, response = {};
                        params.page = params.page || 1;
                        if (Array.isArray(data)) {
                            results = data;
                        } else if (typeof data === 'object') {
                            results = data.results;
                            more = data.more;
                        } else {
                            results = [];
                        }
                        response.results = results;
                        if (opts.scroll) {
                            response.pagination = { more: more };
                        }

                        return response;
                    }
                }
            };
        }

        optionsResult = $.extend(optionsResult, optionsAjax);
        
        $elt.select2(optionsResult);
    });

    return this;
};