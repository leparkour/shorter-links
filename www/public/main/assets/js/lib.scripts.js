var cfg = {
	loc: window.location,
	// theme: (typeof cfg.theme !== "undefined" ? cfg.theme : null),
};

(function(Function_prototype) {
	Function_prototype.debounce = function(delay, ctx) {
        var fn = this, timer;
        return function() {
            var args = arguments, that = this;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(ctx || that, args);
            }, delay);
        };
	};
})(Function.prototype);

(function($) {
	$.fn.getAttr = function() {

		var obj = {};
		$.each(this[0].attributes, function() {
			if(this.specified) {
				obj[this.name] = this.value;
			}
		});
		return obj;
	}
})(jQuery);

function isFunc(func) {
    return typeof window[func] !== 'undefined' && $.isFunction(window[func]);
}
function callMyFunc( fc ) {
	if(typeof fc.fc !== undefined && isFunc(fc.fc)) {
		var func = fc.fc;
		window[func](fc);
		return;
	}
	console.warn("Function: "+fc.fc+" not exists.");
	return!1;
}

var ajaxData = {};

// Ajax sending
function sendAjax(that, thisData) {
	var thatUrl = '?rnv='+new Date().getTime(),
	attributes = that.getAttr(),
	thisData = thisData || new FormData();

	for(key in ajaxData) {
		if( Array.isArray(ajaxData[key]) ) {
			$.each(ajaxData[key], function(k, v) {
				thisData.append(key, v);
			});
		} else {
			thisData.append(key, ajaxData[key]);
		}
	}
	for(key in attributes) {
		if(key.indexOf('ajax-') === 0) {
			thisData.append(key.substring(5), attributes[key]);
		}
	}
	const controller = new AbortController();

	fetch(thatUrl, {
		method: 'POST',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
		mode: "cors",
		credentials: "include",
		body: thisData,
		signal: controller.signal,
	}).then((response) => {
		if( response.status !== 200 ) {
			console.log('Looks like there was a problem. Status Code: ' + response.status);
			return;
		}
		return response.json();
	}).then((data) => {
		if(typeof data.fc !== "undefined") {
			data.ele = that;
			callMyFunc(data);
		}

		if(typeof data._fc !== "undefined") {
			var tmpFn = new Function(data._fc);
			tmpFn();
		}

		if( typeof data.notify === "undefined" || data.notify !== false ) {
			$.notify(data.msg, data.status);
		}
	}).catch(function(err) {
		console.log('Fetch Error :-S', err);
	});

	that.addClass('disabled-send');
	that.find("button[type='submit']").attr("disabled", true);

	setTimeout(function() {
		that.removeClass('disabled-send');
		that.find("button[type='submit']").removeAttr("disabled");
	}, 1000);
}

$(document).on("submit", '.ajax-form', function(event) {
	event.preventDefault();
	var that = $(this), thisData = new FormData(that[0]);

	if( that.hasClass('disabled-send') !== false ) return;

	sendAjax(that, thisData);
}).on('click', '.ajax-click', function(event) {
	event.preventDefault();
	var that = $(this), obj = {};

	if( that.hasClass('disabled-send') !== false ) return;

	sendAjax(that);
}).on('input', '.ajax-input', function(event) {
	event.preventDefault();
	var that = $(this);

	thisData.append('query', that.val());

	sendAjax(that);
}.debounce(1000)).on('change', '.ajax-change', function(event) {
	event.preventDefault();
	var that = $(this), thatForm = that.closest("form"), thisData = new FormData();
	if( thatForm.length > 0 && !that.hasClass('not-form') ) {
		thisData = new FormData(thatForm[0]);
	}

	if( thatForm.length <= 0 || that.hasClass('not-form') ) {
		thisData.append('query', that.val());
	}

	sendAjax(that, thisData);
}).on('change', '.ajax-form-onchange', function(e) {
	$(this).submit();
});