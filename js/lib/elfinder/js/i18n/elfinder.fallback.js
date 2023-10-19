(function(factory) {
	if (typeof define === 'function' && define.noamd) {
		define(factory);
	} else if (typeof exports !== 'undefined') {
		module.exports = factory();
	} else {
		factory();
	}
}(this, function() {
	return void 0;
}));
