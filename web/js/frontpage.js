$( document ).ready(function() {
    $('.product-typeahead').typeahead({
        minLength: 3,
        source: function (query, process) {
            return $.getJSON('/ajax_search/product/'+encodeURIComponent(query), {}, function (data) {
                var resultList = [];
                $.each(data, function(index, value) {
                    if(resultList.indexOf(value.product) == -1) {
                        resultList.push(value.product);
                    }
                });
                return process(resultList);
            });
        }
    });
    $('.area-typeahead').typeahead({
        minLength: 3,
        source: function (query, process) {
            return $.getJSON('/ajax_search/store/'+encodeURIComponent(query), {}, function (data) {
                var resultList = [];
                $.each(data, function(index, value) {
                    $.each(value.store.split(/[\s\/]+/), function(sindex, svalue) {
                        if(resultList.indexOf(svalue) == -1 && svalue.length > 3) {
                            resultList.push(svalue);
                        }
                    });
                });
                return process(resultList);
            });
        }
    });
});