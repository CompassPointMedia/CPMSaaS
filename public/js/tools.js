//global functions

//string tools
function parse_str (str, array) {
	// http://locutus.io/php/strings/parse_str/
	var strArr = String(str).replace(/^&/, '').replace(/&$/, '').split('&')
	var sal = strArr.length;
	var i, j, ct, p;
	var lastObj;
	var obj;
	var undef;
	var chr;
	var tmp;
	var key;
	var value;
	var postLeftBracketPos;
	var keys;
	var keysLen;

	var _fixStr = function (str) {
		return decodeURIComponent(str.replace(/\+/g, '%20'))
	}

	var $global = (typeof window !== 'undefined' ? window : global)
	$global.$locutus = $global.$locutus || {}
	var $locutus = $global.$locutus
	$locutus.php = $locutus.php || {}

	if (!array) {
		array = $global
	}

	for (i = 0; i < sal; i++) {
		tmp = strArr[i].split('=')
		key = _fixStr(tmp[0])
		value = (tmp.length < 2) ? '' : _fixStr(tmp[1])

		while (key.charAt(0) === ' ') {
			key = key.slice(1)
		}
		if (key.indexOf('\x00') > -1) {
			key = key.slice(0, key.indexOf('\x00'))
		}
		if (key && key.charAt(0) !== '[') {
			keys = []
			postLeftBracketPos = 0
			for (j = 0; j < key.length; j++) {
				if (key.charAt(j) === '[' && !postLeftBracketPos) {
					postLeftBracketPos = j + 1
				} else if (key.charAt(j) === ']') {
					if (postLeftBracketPos) {
						if (!keys.length) {
							keys.push(key.slice(0, postLeftBracketPos - 1))
						}
						keys.push(key.substr(postLeftBracketPos, j - postLeftBracketPos))
						postLeftBracketPos = 0
						if (key.charAt(j + 1) !== '[') {
							break
						}
					}
				}
			}
			if (!keys.length) {
				keys = [key]
			}
			for (j = 0; j < keys[0].length; j++) {
				chr = keys[0].charAt(j)
				if (chr === ' ' || chr === '.' || chr === '[') {
					keys[0] = keys[0].substr(0, j) + '_' + keys[0].substr(j + 1)
				}
				if (chr === '[') {
					break
				}
			}

			obj = array
			for (j = 0, keysLen = keys.length; j < keysLen; j++) {
				key = keys[j].replace(/^['"]/, '').replace(/['"]$/, '')
				lastObj = obj
				if ((key !== '' && key !== ' ') || j === 0) {
					if (obj[key] === undef) {
						obj[key] = {}
					}
					obj = obj[key]
				} else {
					// To insert new dimension
					ct = -1
					for (p in obj) {
						if (obj.hasOwnProperty(p)) {
							if (+p > ct && p.match(/^\d+$/g)) {
								ct = +p
							}
						}
					}
					key = ct + 1
				}
			}
			lastObj[key] = value
		}
	}
}

function snakeCase(str){
	if(str.match(/_/)){
		var str = str.split('_'), i;
		for(i in str){
			if(str[i].match(/^[a-z]/)){
				//todo: "How To Win Friends and Influence People" - library for handling the `and` here
				//todo: non-Latin-1 cases
				str[i] = str[i].substr(0,1).toUpperCase() + str[i].substr(1);
			}
		}
		str = str.join(' ');
	}
	return str;
}

function camelCase(str, humble){
	str = str.replace(/([a-z])([A-Z])/g, '$1 $2');
	if(typeof humble !== 'undefined' && humble === false){
		return str;
	}
	return str.substring(0, 1).toUpperCase() + str.substring(1, str.length);
}

function nl2br(str){
	if(typeof str !== 'string') return str;
	return str.replace(/[\n\r]/g, '<br />' + '\n');
}

function rand(){
	return Math.random().toString().replace(/^[01]\./,'');
}

var md5 = function(d){
	result = M(V(Y(X(d),8*d.length)));return result.toLowerCase()};function M(d){for(var _,m="0123456789ABCDEF",f="",r=0;r<d.length;r++)_=d.charCodeAt(r),f+=m.charAt(_>>>4&15)+m.charAt(15&_);return f}function X(d){for(var _=Array(d.length>>2),m=0;m<_.length;m++)_[m]=0;for(m=0;m<8*d.length;m+=8)_[m>>5]|=(255&d.charCodeAt(m/8))<<m%32;return _}function V(d){for(var _="",m=0;m<32*d.length;m+=8)_+=String.fromCharCode(d[m>>5]>>>m%32&255);return _}function Y(d,_){d[_>>5]|=128<<_%32,d[14+(_+64>>>9<<4)]=_;for(var m=1732584193,f=-271733879,r=-1732584194,i=271733878,n=0;n<d.length;n+=16){var h=m,t=f,g=r,e=i;f=md5_ii(f=md5_ii(f=md5_ii(f=md5_ii(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_hh(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_gg(f=md5_ff(f=md5_ff(f=md5_ff(f=md5_ff(f,r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+0],7,-680876936),f,r,d[n+1],12,-389564586),m,f,d[n+2],17,606105819),i,m,d[n+3],22,-1044525330),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+4],7,-176418897),f,r,d[n+5],12,1200080426),m,f,d[n+6],17,-1473231341),i,m,d[n+7],22,-45705983),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+8],7,1770035416),f,r,d[n+9],12,-1958414417),m,f,d[n+10],17,-42063),i,m,d[n+11],22,-1990404162),r=md5_ff(r,i=md5_ff(i,m=md5_ff(m,f,r,i,d[n+12],7,1804603682),f,r,d[n+13],12,-40341101),m,f,d[n+14],17,-1502002290),i,m,d[n+15],22,1236535329),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+1],5,-165796510),f,r,d[n+6],9,-1069501632),m,f,d[n+11],14,643717713),i,m,d[n+0],20,-373897302),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+5],5,-701558691),f,r,d[n+10],9,38016083),m,f,d[n+15],14,-660478335),i,m,d[n+4],20,-405537848),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+9],5,568446438),f,r,d[n+14],9,-1019803690),m,f,d[n+3],14,-187363961),i,m,d[n+8],20,1163531501),r=md5_gg(r,i=md5_gg(i,m=md5_gg(m,f,r,i,d[n+13],5,-1444681467),f,r,d[n+2],9,-51403784),m,f,d[n+7],14,1735328473),i,m,d[n+12],20,-1926607734),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+5],4,-378558),f,r,d[n+8],11,-2022574463),m,f,d[n+11],16,1839030562),i,m,d[n+14],23,-35309556),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+1],4,-1530992060),f,r,d[n+4],11,1272893353),m,f,d[n+7],16,-155497632),i,m,d[n+10],23,-1094730640),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+13],4,681279174),f,r,d[n+0],11,-358537222),m,f,d[n+3],16,-722521979),i,m,d[n+6],23,76029189),r=md5_hh(r,i=md5_hh(i,m=md5_hh(m,f,r,i,d[n+9],4,-640364487),f,r,d[n+12],11,-421815835),m,f,d[n+15],16,530742520),i,m,d[n+2],23,-995338651),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+0],6,-198630844),f,r,d[n+7],10,1126891415),m,f,d[n+14],15,-1416354905),i,m,d[n+5],21,-57434055),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+12],6,1700485571),f,r,d[n+3],10,-1894986606),m,f,d[n+10],15,-1051523),i,m,d[n+1],21,-2054922799),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+8],6,1873313359),f,r,d[n+15],10,-30611744),m,f,d[n+6],15,-1560198380),i,m,d[n+13],21,1309151649),r=md5_ii(r,i=md5_ii(i,m=md5_ii(m,f,r,i,d[n+4],6,-145523070),f,r,d[n+11],10,-1120210379),m,f,d[n+2],15,718787259),i,m,d[n+9],21,-343485551),m=safe_add(m,h),f=safe_add(f,t),r=safe_add(r,g),i=safe_add(i,e)}return Array(m,f,r,i)}function md5_cmn(d,_,m,f,r,i){return safe_add(bit_rol(safe_add(safe_add(_,d),safe_add(f,i)),r),m)}function md5_ff(d,_,m,f,r,i,n){return md5_cmn(_&m|~_&f,d,_,r,i,n)}function md5_gg(d,_,m,f,r,i,n){return md5_cmn(_&f|m&~f,d,_,r,i,n)}function md5_hh(d,_,m,f,r,i,n){return md5_cmn(_^m^f,d,_,r,i,n)}function md5_ii(d,_,m,f,r,i,n){return md5_cmn(m^(_|~f),d,_,r,i,n)}function safe_add(d,_){var m=(65535&d)+(65535&_);return(d>>16)+(_>>16)+(m>>16)<<16|65535&m}function bit_rol(d,_){return d<<_|d>>>32-_
}



//more or less array tools
function subserialize(key, obj) {
	// this doesn't handle multilevel arrays - https://stackoverflow.com/a/1714899/4127646 for a solution
	var str = [], i, arr = (obj instanceof Array)
	for(i in obj){
		if(!obj.hasOwnProperty(i)) continue;
		str.push(key + '[' + (arr ? '' : encodeURI(i)) + ']=' + encodeURI(obj[i]));
	}
	return str.join("&");
}

function array_subkey(haystack, needle, key){
	for(var i in haystack){
		if(haystack[i][key] == needle) return haystack[i];
	}
	return false;
}

function array_keys(obj) {
	output = [];
	for(var key in obj) output.push(key);
	return output;
}

function in_array(needle, haystack) {
	var length = haystack.length;
	for(var i = 0; i < length; i++) {
		if(haystack[i] == needle) return true;
		if(typeof haystack[i] === 'string' && typeof needle === 'string' && haystack[i].toLowerCase() === needle.toLowerCase()) return true;
	}
	return false;
}

function sizeOf(obj){
	var type =  typeof obj;
	// null has no length
	if(type === 'undefined' || obj === null) return null;
	if(type === 'string') return obj.length;
	if(type === 'object' || type === 'array'){
		var length = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) length++;
		}
		return length;
	}
	return obj.length ? obj.length : 0;
}

function object_to_query_string(obj, prefix) {
	// https://stackoverflow.com/questions/1714786/query-string-encoding-of-a-javascript-object
	var str = [],
		p;
	for (p in obj) {
		if (obj.hasOwnProperty(p)) {
			var k = prefix ? prefix + "[" + p + "]" : p,
				v = obj[p];
			str.push((v !== null && typeof v === "object") ?
				object_to_query_string(v, k) :
				k + "=" + encodeURIComponent(v));
		}
	}
	return str.join("&");
}

function assign_color(obj, colors, colorKey, offset, key, labelField){
	/*
	2018-09-19 <sfullman@presidio.com>
	This will assign a color to elements on their sorted md5 hashes; given a set of colors, each string will get a unique color assigned until the full list of colors has been used, then colors will be re-assigned by modulus
	Note: obviously a change in the number of elements will change assigned colors in an unpredictable way.  In other words, this does not assign a persistent color to a given string.
	 */
	if(typeof colors === 'undefined') colors = x11colors;
	if(typeof colorKey === 'undefined') colorKey = 3;
	if(typeof offset === 'undefined') offset = 100;
	if(typeof key === 'undefined') key = 'backgroundColor';
	if(typeof labelField === 'undefined') labelField = 'label';

	var m, md5s = [], keys = {};
	for(var i in obj){
		if(obj[i][key]) continue;
		m = md5(String(obj[i][labelField]).toLowerCase());

		//transpose md5s to get a new value for duplicate labels
		while(typeof keys[m] !== 'undefined') m = md5(m);
		md5s.push(m);
		keys[m] = i;
	}
	if(md5s.length) {
		md5s.sort();
		var ref;
		for (var j in md5s) {
			ref = keys[md5s[j]];
			obj[ref][key] = colors[offset % colors.length][colorKey];
			offset++;
		}
	}
	return obj;
}


//handle default booleans for items that might not have been defined
function _true(item){
	if(typeof item === 'undefined') return true;
	//accept truthy values
	return (item == true)
}

function _false(item){
	if(typeof item === 'undefined') return false;
	//accept falsey values
	return !(item == false)
}

function _undefined(item){
	return (typeof item === 'undefined');
}

function _key(obj, key){
	if(typeof obj !== 'object') return false;
	if(typeof obj[key] === 'undefined') return key;
	return obj[key];
}

//date and time tools
function newDate(Y,m,d,H,i,s){
	//PHP-ish date() parameters..
	const t = new Date();
	if(!Y) Y=t.getFullYear();
	if(!m) {
		m = t.getMonth();
	}else{
		m = parseInt(m.replace(/^0/,'')) - 1; //0-based
	}
	if(!d){
		d = t.getDay();
	}
	if(typeof H !=='undefined'){
		//we must have a full date component; it must make sense
		return new Date(Y, m, d, H, i, s);
	}else{
		return new Date(Y, m, d, 0, 0, 0);
	}
}

function createDate(date, format){
	/**
	 * Create a JS Date object from any recognized format
	 */
	if(typeof date === 'undefined' || date === null) return '';
	if(typeof date === 'string' && !date.length) return '';
	if(typeof date === 'object'){
		// already an object
	}else if(date.match(/^[0-9]+$/)){
		// Unix timestamp
		date = new Date(parseInt(date) * 1000);
		date.unparsed = arguments[0];
		date.hasTime = true;
		date.unix = true;
	}else {
		// string value in db format [YYYY-MM-DD] [HH:II:SS] (one or both)
		/* todo: improve this to the point where it can differentiate better between date- or time-only, as well as non-db formats like 7/15/2018 or July 4th 1982 or even today -1 week */
		var unparsed = date;

		// hack for dates with slashes
		var slash = date.split('/');
		if(slash.length === 3){
			date =
				('20' + slash[2]).substr(-4) + '-' +
				('00' + slash[0]).substr(-2) + '-' +
				('00' + slash[1]).substr(-2);
		}

		// IE not accepting spread: date = newDate(... date.split(/[-: ]/));
		// --- same as newDate() function above ---
		var spread = date.split(/[-: ]/);
		var Y = typeof spread[0] !== 'undefined' ? spread[0] : '';
		var m = typeof spread[1] !== 'undefined' ? spread[1] : '';
		var d = typeof spread[2] !== 'undefined' ? spread[2] : '';
		if(unparsed.indexOf(':') > -1){
			if(typeof spread[3] !== 'undefined') var H = spread[3];
			if(typeof spread[4] !== 'undefined') var i = spread[4];
			if(typeof spread[5] !== 'undefined') var s = spread[5];
		}

		//PHP-ish date() parameters..
		//todo: get rid of this block; if we need current date for year reference, get it earlier
		const t = new Date();
		if(!Y) Y=t.getFullYear();
		if(!m) {
			m = t.getMonth();
		}else{
			m = parseInt(m.replace(/^0/,'')) - 1; //0-based
		}
		if(!d){
			d = t.getDay();
		}

		if(typeof H !== 'undefined'){
			//we must have a full date component; it must make sense
			date = new Date(Y, m, d, H, i, s);
		}else{
			date = new Date(Y, m, d, 0, 0, 0);
		}
		// -----------------------------------------

		date.unparsed = unparsed;
		date.hasTime = (typeof H !== 'undefined');
		date.unix = false;
	}

	if(typeof format !== 'undefined'){
		//return formatted string
		if(date.toString() === 'Invalid Date' && date.unparsed){
			return date.unparsed;
		}
		var str, hours, am;
		if(format === 'ymdhis') {
			str =
				date.getFullYear() + '-' +
				('00' + (date.getMonth() + 1)).substr(-2) + '-' +
				('00' + date.getDate()).substr(-2) + ' ' +
				('00' + date.getHours()).substr(-2) + ':' +
				('00' + date.getMinutes()).substr(-2) + ':' +
				('00' + date.getSeconds()).substr(-2);
			return str;
		}else if(format === 'YmdHis'){
			str =
				date.getFullYear() +
				('00' + (date.getMonth() + 1)).substr(-2) +
				('00' + date.getDate()).substr(-2) +
				('00' + date.getHours()).substr(-2) +
				('00' + date.getMinutes()).substr(-2) +
				('00' + date.getSeconds()).substr(-2);
			return str;
		}else if(format.toLowerCase() === 'standard'){
			hours = date.getHours();
			am = hours > 11 ? 'PM' : 'AM';
			if(hours > 11){
				hours = hours - 12;
			}
			if(hours === 0) hours = 12;
			str =
				('00' + (date.getMonth() + 1)).substr(-2) + '/' +
				('00' + date.getDate()).substr(-2) + '/' +
				date.getFullYear() + ' ' +
				hours + ':' +
				('00' + date.getMinutes()).substr(-2) + ' ' +
				am;
			return str;
		}else if(format.toLowerCase() === 'm/d/y'){
			str =
				('00' + (date.getMonth() + 1)).substr(-2) + '/' +
				('00' + date.getDate()).substr(-2) + '/' +
				date.getFullYear();
			return str;
		}
	}

	return date;
}

function parseDateToString(str, obj){
	var a, Y, m, d, H, i, s;
	var trans = {
		'Jan': '01',
		'Feb': '02',
		'Mar': '03',
		'Apr': '04',
		'May': '05',
		'Jun': '06',
		'Jul': '07',
		'Aug': '08',
		'Sep': '09',
		'Oct': '10',
		'Nov': '11',
		'Dec': '12'
	}
	a = str.split(' ');
	if(a[a.length - 1].match(/ [0-9]{4}$/)){
		//IE 11 format
		Y = a[a.length - 1];
	}else{
		Y = a[3];
	}
	m = trans[a[1]];
	d = a[2];
	a = str.split(':');
	H = a[0].substring(a[0].length - 2);
	i = a[1];
	s = a[2].substring(0, 2);
	if(obj){
		return {
			Y: Y, m: m, d: d, H: H, i: i, s: s, str: str,
		}
	}
	return Y + '-' + m + '-' + d + ' ' + H + ':' + i + ':' + s;
}

function epochToLocal(epoch){
	/**
	 * @author Jeremy Nicolls
	 * @type {Date}
	 */
	// example output: 09-04-2018 23:22

	var myDate            =  new Date( epoch * 1000);
	var LocalTime 		  = myDate.toLocaleString();
	var DisplayYear 	  = myDate.getFullYear();
	var DisplayMonth 	  = myDate.getMonth() + 1;
	var DisplayDay 		  = myDate.getDay(); // returns the weekday as a number (0-6):
	var DisplayDate 	  = myDate.getDate();
	var DisplayHour 	  = myDate.getHours();
	var DisplayMin 		  = myDate.getMinutes();
	var DisplaySec 		  = myDate.getSeconds();

	DisplayMonth 		  = ("0" + DisplayMonth).substr(-2);
	DisplayDate 		  = ("0" + DisplayDate).substr(-2);
	DisplayHour 		  = ("0" + DisplayHour).substr(-2);
	DisplayMin 			  = ("0" + DisplayMin).substr(-2);

	return output = DisplayMonth + "-" + DisplayDate + "-" + DisplayYear + " " + DisplayHour + ":" + DisplayMin;
}

function epochToLocalDateTime(epoch){
	/**
	 * @author Jeremy Nicolls
	 * @type {Date}
	 */
// example output: 9/4/2018, 11:22:46 PM
	var myDate      = new Date( epoch * 1000);
	var LocalTime       = myDate.toLocaleString();
	return LocalTime;
}

//for future inclusion:
//https://github.com/kvz/locutus/blob/master/src/php/datetime/strtotime.js
//however parsing a simple string like 3/15 goes to 3/15/2001 vs. current year

function strtotime (text, now) {
	//  discuss at: http://locutus.io/php/strtotime/
	// original by: Caio Ariede (http://caioariede.com)
	// improved by: Kevin van Zonneveld (http://kvz.io)
	// improved by: Caio Ariede (http://caioariede.com)
	// improved by: A. Matías Quezada (http://amatiasq.com)
	// improved by: preuter
	// improved by: Brett Zamir (http://brett-zamir.me)
	// improved by: Mirko Faber
	//    input by: David
	// bugfixed by: Wagner B. Soares
	// bugfixed by: Artur Tchernychev
	// bugfixed by: Stephan Bösch-Plepelits (http://github.com/plepe)
	//      note 1: Examples all have a fixed timestamp to prevent
	//      note 1: tests to fail because of variable time(zones)
	//   example 1: strtotime('+1 day', 1129633200)
	//   returns 1: 1129719600
	//   example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200)
	//   returns 2: 1130425202
	//   example 3: strtotime('last month', 1129633200)
	//   returns 3: 1127041200
	//   example 4: strtotime('2009-05-04 08:30:00 GMT')
	//   returns 4: 1241425800
	//   example 5: strtotime('2009-05-04 08:30:00+00')
	//   returns 5: 1241425800
	//   example 6: strtotime('2009-05-04 08:30:00+02:00')
	//   returns 6: 1241418600
	//   example 7: strtotime('2009-05-04T08:30:00Z')
	//   returns 7: 1241425800

	var parsed
	var match
	var today
	var year
	var date
	var days
	var ranges
	var len
	var times
	var regex
	var i
	var fail = false

	if (!text) {
		return fail
	}

	// Unnecessary spaces
	text = text.replace(/^\s+|\s+$/g, '')
		.replace(/\s{2,}/g, ' ')
		.replace(/[\t\r\n]/g, '')
		.toLowerCase()

	// in contrast to php, js Date.parse function interprets:
	// dates given as yyyy-mm-dd as in timezone: UTC,
	// dates with "." or "-" as MDY instead of DMY
	// dates with two-digit years differently
	// etc...etc...
	// ...therefore we manually parse lots of common date formats
	var pattern = new RegExp([
		'^(\\d{1,4})',
		'([\\-\\.\\/:])',
		'(\\d{1,2})',
		'([\\-\\.\\/:])',
		'(\\d{1,4})',
		'(?:\\s(\\d{1,2}):(\\d{2})?:?(\\d{2})?)?',
		'(?:\\s([A-Z]+)?)?$'
	].join(''))
	match = text.match(pattern)

	if (match && match[2] === match[4]) {
		if (match[1] > 1901) {
			switch (match[2]) {
				case '-':
					// YYYY-M-D
					if (match[3] > 12 || match[5] > 31) {
						return fail
					}

					return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
				case '.':
					// YYYY.M.D is not parsed by strtotime()
					return fail
				case '/':
					// YYYY/M/D
					if (match[3] > 12 || match[5] > 31) {
						return fail
					}

					return new Date(match[1], parseInt(match[3], 10) - 1, match[5],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
			}
		} else if (match[5] > 1901) {
			switch (match[2]) {
				case '-':
					// D-M-YYYY
					if (match[3] > 12 || match[1] > 31) {
						return fail
					}

					return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
				case '.':
					// D.M.YYYY
					if (match[3] > 12 || match[1] > 31) {
						return fail
					}

					return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
				case '/':
					// M/D/YYYY
					if (match[1] > 12 || match[3] > 31) {
						return fail
					}

					return new Date(match[5], parseInt(match[1], 10) - 1, match[3],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
			}
		} else {
			switch (match[2]) {
				case '-':
					// YY-M-D
					if (match[3] > 12 || match[5] > 31 || (match[1] < 70 && match[1] > 38)) {
						return fail
					}

					year = match[1] >= 0 && match[1] <= 38 ? +match[1] + 2000 : match[1]
					return new Date(year, parseInt(match[3], 10) - 1, match[5],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
				case '.':
					// D.M.YY or H.MM.SS
					if (match[5] >= 70) {
						// D.M.YY
						if (match[3] > 12 || match[1] > 31) {
							return fail
						}

						return new Date(match[5], parseInt(match[3], 10) - 1, match[1],
								match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
					}
					if (match[5] < 60 && !match[6]) {
						// H.MM.SS
						if (match[1] > 23 || match[3] > 59) {
							return fail
						}

						today = new Date()
						return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
								match[1] || 0, match[3] || 0, match[5] || 0, match[9] || 0) / 1000
					}

					// invalid format, cannot be parsed
					return fail
				case '/':
					// M/D/YY
					if (match[1] > 12 || match[3] > 31 || (match[5] < 70 && match[5] > 38)) {
						return fail
					}

					year = match[5] >= 0 && match[5] <= 38 ? +match[5] + 2000 : match[5]
					return new Date(year, parseInt(match[1], 10) - 1, match[3],
							match[6] || 0, match[7] || 0, match[8] || 0, match[9] || 0) / 1000
				case ':':
					// HH:MM:SS
					if (match[1] > 23 || match[3] > 59 || match[5] > 59) {
						return fail
					}

					today = new Date()
					return new Date(today.getFullYear(), today.getMonth(), today.getDate(),
							match[1] || 0, match[3] || 0, match[5] || 0) / 1000
			}
		}
	}

	// other formats and "now" should be parsed by Date.parse()
	if (text === 'now') {
		return now === null || isNaN(now)
			? new Date().getTime() / 1000 | 0
			: now | 0
	}
	if (!isNaN(parsed = Date.parse(text))) {
		return parsed / 1000 | 0
	}
	// Browsers !== Chrome have problems parsing ISO 8601 date strings, as they do
	// not accept lower case characters, space, or shortened time zones.
	// Therefore, fix these problems and try again.
	// Examples:
	//   2015-04-15 20:33:59+02
	//   2015-04-15 20:33:59z
	//   2015-04-15t20:33:59+02:00
	pattern = new RegExp([
		'^([0-9]{4}-[0-9]{2}-[0-9]{2})',
		'[ t]',
		'([0-9]{2}:[0-9]{2}:[0-9]{2}(\\.[0-9]+)?)',
		'([\\+-][0-9]{2}(:[0-9]{2})?|z)'
	].join(''))
	match = text.match(pattern)
	if (match) {
		// @todo: time zone information
		if (match[4] === 'z') {
			match[4] = 'Z'
		} else if (match[4].match(/^([+-][0-9]{2})$/)) {
			match[4] = match[4] + ':00'
		}

		if (!isNaN(parsed = Date.parse(match[1] + 'T' + match[2] + match[4]))) {
			return parsed / 1000 | 0
		}
	}

	date = now ? new Date(now * 1000) : new Date()
	days = {
		'sun': 0,
		'mon': 1,
		'tue': 2,
		'wed': 3,
		'thu': 4,
		'fri': 5,
		'sat': 6
	}
	ranges = {
		'yea': 'FullYear',
		'mon': 'Month',
		'day': 'Date',
		'hou': 'Hours',
		'min': 'Minutes',
		'sec': 'Seconds'
	}

	function lastNext (type, range, modifier) {
		var diff
		var day = days[range]

		if (typeof day !== 'undefined') {
			diff = day - date.getDay()

			if (diff === 0) {
				diff = 7 * modifier
			} else if (diff > 0 && type === 'last') {
				diff -= 7
			} else if (diff < 0 && type === 'next') {
				diff += 7
			}

			date.setDate(date.getDate() + diff)
		}
	}

	function process (val) {
		// @todo: Reconcile this with regex using \s, taking into account
		// browser issues with split and regexes
		var splt = val.split(' ')
		var type = splt[0]
		var range = splt[1].substring(0, 3)
		var typeIsNumber = /\d+/.test(type)
		var ago = splt[2] === 'ago'
		var num = (type === 'last' ? -1 : 1) * (ago ? -1 : 1)

		if (typeIsNumber) {
			num *= parseInt(type, 10)
		}

		if (ranges.hasOwnProperty(range) && !splt[1].match(/^mon(day|\.)?$/i)) {
			return date['set' + ranges[range]](date['get' + ranges[range]]() + num)
		}

		if (range === 'wee') {
			return date.setDate(date.getDate() + (num * 7))
		}

		if (type === 'next' || type === 'last') {
			lastNext(type, range, num)
		} else if (!typeIsNumber) {
			return false
		}

		return true
	}

	times = '(years?|months?|weeks?|days?|hours?|minutes?|min|seconds?|sec' +
		'|sunday|sun\\.?|monday|mon\\.?|tuesday|tue\\.?|wednesday|wed\\.?' +
		'|thursday|thu\\.?|friday|fri\\.?|saturday|sat\\.?)'
	regex = '([+-]?\\d+\\s' + times + '|' + '(last|next)\\s' + times + ')(\\sago)?'

	match = text.match(new RegExp(regex, 'gi'))
	if (!match) {
		return fail
	}

	for (i = 0, len = match.length; i < len; i++) {
		if (!process(match[i])) {
			return fail
		}
	}

	return (date.getTime() / 1000)
}

//specialty tools
function lazyAttributes(obj, specs){
	/**
	 * sample specification = [
	 *      'layout.columns',
	 *      function(obj){ .. return array of elements .. },
	 *      {var1: 'value-1', var2: 'value-2'}
	 * ]
	 * will run the following:
	 *  obj.layout.columns.field1.var1 = 'value-1';
	 *  obj.layout.columns.field1.var2 = 'value-2';
	 *  .. for field2, field3, etc..
	 */
	var i, j, k, spec, iter, exe, str;
	for(i in specs){
		exe = 'obj';
		spec = specs[i];
		if(typeof spec[0] === 'string') spec[0] = spec[0].split('.');
		for(j in spec[0]){
			exe += '[\'' + spec[0][j] + '\']';
		}
		if(typeof spec[1] === 'function'){
			iter = spec[1](obj);
		}else if(typeof spec[1] === 'object'){
			iter = spec[1];
		}
		for(j in iter){
			for(k in spec[2]){
				str = exe + '[\'' + iter[j] + '\'][\'' + k + '\'] = ';
				if(typeof spec[2][k] === 'boolean'){
					str += (spec[2][k] ? 'true' : 'false');
				}else if(typeof spec[2][k] === 'number'){
					str += spec[2][k];
				}else if(typeof spec[2][k] === 'string'){
					str += "'" + spec[2][k] + "'";
				}else{
					str += spec[2][k];
				}
				str += ';';
				try{
					eval(str);
				}catch(e){
					console.log('Error in eval\'d string (' + str + '): ' + e.message);
				}
			}
		}
	}
}

function dynamicSort(property, datatype) {
	var dir = 1;
	if(property.indexOf('-') === 0){
		property = property.substring(1);
		dir = -1;
	}
	return function (obj1,obj2) {
		var o1, o2, i;
		if(datatype.match('int')){
			//we are trusting that the values are in fact integers
			o1 = (obj1[property] === null || obj1[property] === '' ? 0 : parseInt(obj1[property]));
			o2 = (obj2[property] === null || obj2[property] === '' ? 0 : parseInt(obj2[property]));
			i = ( o1 > o2 ? 1
				: o1 < o2 ? -1 : 0 );
		}else{
			o1 = (obj1[property] === null ? '' : obj1[property]);
			o2 = (obj2[property] === null ? '' : obj2[property]);
			i = ( o1.toString().toLowerCase() > o2.toString().toLowerCase() ? 1
				: o1.toString().toLowerCase() < o2.toString().toLowerCase() ? -1 : 0 );
		}
		return dir * i;
	}
}

function min_ajax(config){
	/**
	 * This function can be run with just config.uri,
	 * config.params is usually also required depending on what your API expects.
	 * config.params should be in name-value pair format and properly escaped
	 */
	var params = '', xhr = new XMLHttpRequest();
	xhr.open(config.method ? config.method.toUpperCase() : 'POST', config.uri);
	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhr.responseType = config.responseType ? config.responseType.toLowerCase() : 'json';
	xhr.onload = function() {
		// this normally happens in IE
		if(xhr.responseType === 'json' && xhr.response && typeof xhr.response === 'string'){
			xhr.response = JSON.parse(xhr.response);
			console.log('intercept string JSON in min_ajax');
			console.log(typeof xhr.response);
		}
		if(typeof config.either === 'function'){
			config.either(xhr);
		}
		if (xhr.status === 200) {
			if(typeof config.success === 'function') config.success(xhr);
		} else {
			// handle this
			if(typeof config.error === 'function') config.error(xhr);
		}
	};
	// for processes that need to work on the xhr before the request is sent
	if(typeof config.before === 'function'){
		//currently no return value
		config.before(xhr);
	}
	//xhr.onload = config.onload;
	if(typeof config.params === 'object'){
		//process variables
	}else{
		//no action needed; assumed correct string format
		params = config.params ? config.params : '';
	}
	xhr.send(params);
	if(typeof config.immediately === 'function'){
		config.immediately(xhr);
	}
}

function recordid_open(){
	var recordid, el;
	if(recordid = hash.recordid){
		recordid = recordid.split(',');
		//for now we only open a single record
		//@todo - handle if record is not present in pagination - how would I handle 101 records i.e. one added
		if((el = document.getElementById('recordid-' + recordid[0])) && _undefined(el.initially_opened)){
			el.initially_opened = true;
			el.click();
		}
	}
}

function observerInsertOrUpdateApplicationParameters(that, focus){
	//`that` and focus not currently used
	var app = {
		page_id: window.page_id ? window.page_id : '',
		page_name: window.page_name ? window.page_name : '',
		page_key: window.page_key ? window.page_key : '',
		/* Note we preserve a zero if the poor blokes live in Greenwich :) */
		tz_offset: typeof window.tz_offset === 'number' ? window.tz_offset : '',
	};
	return app;
}

function observerPostResizeColumn(that, target){
	var params = 'page_id=' + window.page_id + '&page_name=' + window.page_name + '&page_key=' + window.page_key;
	params += '&node=colwidth_' + target.column + '&value=' + target.style.width.replace('px','');
	params += '&type=page';
	console.log(params);
	min_ajax({
		uri: '/api/settings/set',
		params: params,
	});
}

function observerPostDataLoad(that){
	if(Broadscope && Broadscope.remote_settable && typeof Broadscope.remote_settable.page === 'object'){
		try{
			var i, fld, val;
			for(var i in Broadscope.remote_settable.page){
				if(i.match('colwidth_')){
					fld = i.replace('colwidth_', '');
					val = Broadscope.remote_settable.page[i];
					if(!that.layout.columns[fld]){
						that.layout.columns[fld] = {};
					}
					that.layout.columns[fld].width = val;
				}
			}
		}catch(e){
			console.log(e);
		}
	}
}

function position(el, pos, settings){
	/**
	 * Positioning function; started 2018-08-17 and meant to be a generic substitute (eventually) for jQuery positioning functions
	 * @param el
	 * @param pos
	 * @param settings
	 * @return {*}
	 */
	//error checking
	if(typeof el === 'string') el = document.getElementById(el);
	if(typeof pos === 'string') pos = document.getElementById(pos);
	if(typeof el !== 'object' || typeof pos !== 'object') return;

	var viewportOffset = el.getBoundingClientRect();
	var elx = {
		w: el.offsetWidth,
		h: el.offsetHeight,
		// these are relative to the viewport, i.e. the window
		_l: viewportOffset.left,
		r_: window.innerWidth - (viewportOffset.left + el.offsetWidth),
		_t: viewportOffset.top,
		b_: window.innerHeight - (viewportOffset.top + el.offsetHeight),
	};
	var tool = {
		w: pos.offsetWidth,
		h: pos.offsetHeight,
	};

	if(elx.b_ >= tool.h){
		pos.style.top = (elx.h + elx._t + window.scrollY) + 'px';
	}else{
		//put at top
		pos.style.top = (elx._t - tool.h + window.scrollY) + 'px';
	}
	if(elx.w + elx.r_ >= tool.w){
		//align left
		pos.style.left = (elx._l + window.scrollX) + 'px';
	}else{
		pos.style.left = (elx._l + elx.w - tool.w + window.scrollX) + 'px';
	}
	return pos;
}

function dragElement(elmnt) {
	var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
	if (document.getElementById(elmnt.id + "-header")) {
		/* if present, the header is where you move the DIV from:*/
		document.getElementById(elmnt.id + "-header").onmousedown = dragMouseDown;
	} else {
		/* otherwise, move the DIV from anywhere inside the DIV:*/
		elmnt.onmousedown = dragMouseDown;
	}

	function dragMouseDown(e) {
		e = e || window.event;
		e.preventDefault();
		// get the mouse cursor position at startup:
		pos3 = e.clientX;
		pos4 = e.clientY;
		document.onmouseup = closeDragElement;
		// call a function whenever the cursor moves:
		document.onmousemove = elementDrag;
	}

	function elementDrag(e) {
		e = e || window.event;
		e.preventDefault();
		// calculate the new cursor position:
		pos1 = pos3 - e.clientX;
		pos2 = pos4 - e.clientY;
		pos3 = e.clientX;
		pos4 = e.clientY;
		// set the element's new position:
		elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
		elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
	}

	function closeDragElement() {
		console.log('mouse release');
		/* stop moving when mouse button is released:*/
		document.onmouseup = null;
		document.onmousemove = null;
	}
}

function ow(href, w, p, token){
	/* currently v1.01 2008-01-24
	 work to do:
	 determine if the window is open and if there is unsaved data in it
	 indicate in open window that a new url is coming up (gray out over window and text, moving icon pending)
	 remember positions only if it works

	 v1.01 - added the ability to add a random variable to the query (&ResourceToken) for setting quasi resource for a new object
	 */
	if(typeof wins === 'undefined') wins = {};
	var params;
	if(typeof w=='undefined'){
		//develop - this is a "new" and distinct object
	}
	var reg=/^[0-9]+,[0-9]+$/;
	if(p.match(reg)){
		var a=p.split(',');
		params='width='+a[0]+',height='+a[1]+',resizable,scrollbars,status';
	}else{
		params=p;
	}

	//for new objects, creates a unique resource token for generating a "quasi resource" in the database, before the object is saved and recognized as an actual object - this allows for example to add a resource and associate sub-resources with it before the resource has an "official" ID number
	if(token){
		if(token === true) token = 'ResourceToken';
		var hash = href.split('#');
		href = hash[0];
		hash = hash[1] ? hash[1] : '';
		var val = ( parseInt(rand)>8000 ? rand : createDate(new Date(), 'YmdHis') + window.rand().substring(0,5) );
		href += (href.indexOf('?') === -1 ? '?' : '&') + token + '=' + val + (hash ? '#' + hash : '');
		//by default right now, we open this window in an absolutely new window each time - override the value of w
		w = rand;
	}
	wins[w]=window.open(href, w, params);
	try{
		wins[w].focus();
	}catch(e){

	}
	return false;
}

function g_cookie(ck){
	var cVal = document.cookie;
	var cStart = cVal.indexOf(" " + ck + "=");
	if(cStart==-1)	cStart = cVal.indexOf(ck + "=");
	if(cStart == -1){
		cVal = null;
	}else{
		cStart = cVal.indexOf("=", cStart) + 1;
		var cEnd = cVal.indexOf(';', cStart);
		if(cEnd==-1) cEnd=cVal.length;
		cVal = unescape(cVal.substring(cStart,cEnd));
	}
	return cVal;
}

function s_cookie(cName,cVal,cExp,cPath){
	if(typeof cVal=='undefined'){
		//remove the cookie (pass only one variable)
		var date = new Date();
		date.setTime(date.getTime()+(-1*24*60*60*1000));
		var expiry='; expires='+date.toGMTString();
		document.cookie = cName + "="+cVal + expiry+path;
		return;
	}
	cVal = escape(cVal);
	if(typeof cExp == 'undefined'){
		var nw = new Date();
		nw.setMonth(nw.getMonth() + 6);
		var expiry= ";expires="+nw.toGMTString();
	}else if(cExp==0){
		var expiry='';
	}else{
		var date = new Date();
		date.setTime(date.getTime()+(cExp*24*60*60*1000));
		var expiry='; expires='+date.toGMTString();
	}
	if(typeof cPath == 'undefined'){
		var path=';Path=/';
	}else{
		var path = ";Path="+cPath;
	}
	document.cookie = cName + "="+cVal + expiry+path;
}

/**
 * detect IE
 * returns version of IE or false, if browser is not Internet Explorer
 * @source: https://codepen.io/gapcode/pen/vEJNZN
 */
function detectIE() {
	var ua = window.navigator.userAgent;

	// Test values; Uncomment to check result …

	// IE 10
	// ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

	// IE 11
	// ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';

	// Edge 12 (Spartan)
	// ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';

	// Edge 13
	// ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

	var msie = ua.indexOf('MSIE ');
	if (msie > 0) {
		// IE 10 or older => return version number
		return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
	}

	var trident = ua.indexOf('Trident/');
	if (trident > 0) {
		// IE 11 => return version number
		var rv = ua.indexOf('rv:');
		return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
	}

	var edge = ua.indexOf('Edge/');
	if (edge > 0) {
		// Edge (IE 12+) => return version number
		return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
	}

	// other browser
	return false;
}

// Very Broadscope- and Broadridge-specific stuff
function condenseCRQOrSourceNumber(str){
	if(!str) return '';
	if(!str.match(/([A-Z]+)([0]+)([1-9][0-9]+)/)){
		return str;
	}
	return	str.replace(/([A-Z]+)([0]+)([1-9][0-9]+)/,'$1..$3');
}

function duplicate_record(ID, Table, DB, UserName, SourcePage) {
	var agree = confirm("Duplicate record ID # " + ID + " ?");

	if (agree) {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "ExecuteSQL.php", true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send("Action=C&ID=" + ID + "&Table=" + Table + "&DB=" + DB + "&UserName=" + UserName + "&Source=" + SourcePage);
		if(confirm('Click OK to refresh')){
			window.location.reload();
		}
	}
	else {
		return false ;
	}
}

function delete_record(ID, Table, DB, UserName, SourcePage, ja) {
	var agree = confirm("Are you sure you want to delete record ID # " + ID + " ?");

	if (agree) {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("POST", "/system/ExecuteSQL.php", true);
		xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xmlhttp.send("Action=D&ID=" + ID + "&Table=" + Table + "&DB=" + DB + "&UserName=" + UserName + "&Source=" + SourcePage);
		if(confirm('Click OK to refresh')){
			window.location.reload();
		}
	}
	else {
		return false ;
	}
}

function get_user_data(idx){
	if(!window.UserName) return;				//only if logged in

	if(!window.get_user_data_test) return;		//temp block, use to test
	var params = JSON.stringify(observerInsertOrUpdateApplicationParameters());

	min_ajax({
		params: 'update=' + encodeURI(params) + '&PHPSESSID=' + encodeURI(PHPSESSID) + '&bypassSession=1',
		uri: '/api/users/get_user_data/recent',
		success: function(xhr){
			if(typeof xhr.response === 'string'){
				json = JSON.parse(xhr.response);
				console.log('recognized response as string');
			}else{
				json = xhr.response;
			}
			// console.log(json);
			try{
				// todo: minimize this to only items necessary
				window.Broadscope.currentUsers = json.dataset;
				window.currentUsers.dataset = json.dataset;
				//window.currentUsers.$forceUpdate();
			}catch(e){
				console.log(e);
			}
		},
		/* note: these should not be needed after test */
		immediately: function(){
			//restore session cookie
			if(g_cookie('PHPSESSID') !== PHPSESSID){
				console.log('cookie differs, immediate, ' + g_cookie('PHPSESSID') + ':' + PHPSESSID);
				s_cookie('PHPSESSID', PHPSESSID);
			}
		},
		either: function(){
			if(g_cookie('PHPSESSID') !== PHPSESSID){
				console.log('cookie differs, either, ' + g_cookie('PHPSESSID') + ':' + PHPSESSID);
				s_cookie('PHPSESSID', PHPSESSID);
			}
		}
	})
}

function number_format (number, decimals, decPoint, thousandsSep) {
	// http://locutus.io/php/strings/number_format/
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
	var n = !isFinite(+number) ? 0 : +number
	var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
	var sep = (typeof thousandsSep === 'undefined') ? ',' : thousandsSep
	var dec = (typeof decPoint === 'undefined') ? '.' : decPoint
	var s = '';

	var toFixedFix = function (n, prec) {
		var k = Math.pow(10, prec)
		return '' + (Math.round(n * k) / k)
				.toFixed(prec)
	}

	// @todo: for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
	if (s[0].length > 3) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
	}
	if ((s[1] || '').length < prec) {
		s[1] = s[1] || ''
		s[1] += new Array(prec - s[1].length + 1).join('0')
	}

	return s.join(dec)
}

// Library items
var x11colors = [
	[240,248,255,'AliceBlue', 'White'],
	[250,235,215,'AntiqueWhite', 'White'],
	[255,255,0,'Aqua', 'Cyan'],
	[127,255,212,'Aquamarine', 'Cyan'],
	[240,255,255,'Azure', 'White'],
	[245,245,220,'Beige', 'White'],
	[255,228,196,'Bisque', 'Brown'],
	[0,0,0,'Black', 'Gray and black'],
	[255,235,205,'BlanchedAlmond', 'Brown'],
	[0,0,255,'Blue', 'Blue'],
	[138,43,226,'BlueViolet', 'Purple'],
	[165,42,42,'Brown', 'Brown'],
	[222,184,135,'BurlyWood', 'Brown'],
	[95,158,160,'CadetBlue', 'Cyan'],
	[127,255,0,'Chartreuse', 'Green'],
	[210,105,30,'Chocolate', 'Brown'],
	[255,127,80,'Coral', 'Orange'],
	[100,149,237,'CornflowerBlue', 'Blue'],
	[255,248,220,'Cornsilk', 'Brown'],
	[220,20,60,'Crimson', 'Red'],
	[255,255,0,'Cyan', 'Cyan'],
	[0,0,139,'DarkBlue', 'Blue'],
	[139,139,0,'DarkCyan', 'Cyan'],
	[184,134,11,'DarkGoldenrod', 'Brown'],
	[169,169,169,'DarkGray', 'Gray and black'],
	[0,100,0,'DarkGreen', 'Green'],
	[189,183,107,'DarkKhaki', 'Yellow'],
	[139,0,139,'DarkMagenta', 'Purple'],
	[85,107,47,'DarkOliveGreen', 'Green'],
	[255,140,0,'DarkOrange', 'Orange'],
	[153,50,204,'DarkOrchid', 'Purple'],
	[139,0,0,'DarkRed', 'Red'],
	[233,150,122,'DarkSalmon', 'Red'],
	[143,188,143,'DarkSeaGreen', 'Green'],
	[72,61,139,'DarkSlateBlue', 'Purple'],
	[47,79,79,'DarkSlateGray', 'Gray and black'],
	[206,209,0,'DarkTurquoise', 'Cyan'],
	[148,0,211,'DarkViolet', 'Purple'],
	[255,20,147,'DeepPink', 'Pink'],
	[191,255,0,'DeepSkyBlue', 'Blue'],
	[105,105,105,'DimGray', 'Gray and black'],
	[30,144,255,'DodgerBlue', 'Blue'],
	[178,34,34,'FireBrick', 'Red'],
	[255,250,240,'FloralWhite', 'White'],
	[34,139,34,'ForestGreen', 'Green'],
	[255,0,255,'Fuchsia', 'Purple'],
	[220,220,220,'Gainsboro', 'Gray and black'],
	[248,248,255,'GhostWhite', 'White'],
	[255,215,0,'Gold', 'Yellow'],
	[218,165,32,'Goldenrod', 'Brown'],
	[128,128,128,'Gray', 'Gray and black'],
	[0,128,0,'Green', 'Green'],
	[173,255,47,'GreenYellow', 'Green'],
	[240,255,240,'Honeydew', 'White'],
	[255,105,180,'HotPink', 'Pink'],
	[205,92,92,'IndianRed', 'Red'],
	[75,0,130,'Indigo', 'Purple'],
	[255,255,240,'Ivory', 'White'],
	[240,230,140,'Khaki', 'Yellow'],
	[230,230,250,'Lavender', 'Purple'],
	[255,240,245,'LavenderBlush', 'White'],
	[124,252,0,'LawnGreen', 'Green'],
	[255,250,205,'LemonChiffon', 'Yellow'],
	[173,216,230,'LightBlue', 'Blue'],
	[240,128,128,'LightCoral', 'Red'],
	[224,255,255,'LightCyan', 'Cyan'],
	[250,250,210,'LightGoldenrodYellow', 'Yellow'],
	[211,211,211,'LightGray', 'Gray and black'],
	[144,238,144,'LightGreen', 'Green'],
	[255,182,193,'LightPink', 'Pink'],
	[255,160,122,'LightSalmon', 'Red'],
	[32,178,170,'LightSeaGreen', 'Cyan'],
	[135,206,250,'LightSkyBlue', 'Blue'],
	[119,136,153,'LightSlateGray', 'Gray and black'],
	[176,196,222,'LightSteelBlue', 'Blue'],
	[255,255,224,'LightYellow', 'Yellow'],
	[0,255,0,'Lime', 'Green'],
	[50,205,50,'LimeGreen', 'Green'],
	[250,240,230,'Linen', 'White'],
	[255,0,255,'Magenta', 'Purple'],
	[128,0,0,'Maroon', 'Brown'],
	[102,205,170,'MediumAquamarine', 'Green'],
	[0,0,205,'MediumBlue', 'Blue'],
	[186,85,211,'MediumOrchid', 'Purple'],
	[147,112,219,'MediumPurple', 'Purple'],
	[60,179,113,'MediumSeaGreen', 'Green'],
	[123,104,238,'MediumSlateBlue', 'Purple'],
	[250,154,0,'MediumSpringGreen', 'Green'],
	[72,209,204,'MediumTurquoise', 'Cyan'],
	[199,21,133,'MediumVioletRed', 'Pink'],
	[25,25,112,'MidnightBlue', 'Blue'],
	[245,255,250,'MintCream', 'White'],
	[255,228,225,'MistyRose', 'White'],
	[255,228,181,'Moccasin', 'Yellow'],
	[255,222,173,'NavajoWhite', 'Brown'],
	[0,0,128,'Navy', 'Blue'],
	[253,245,230,'OldLace', 'White'],
	[128,128,0,'Olive', 'Green'],
	[107,142,35,'OliveDrab', 'Green'],
	[255,165,0,'Orange', 'Orange'],
	[255,69,0,'OrangeRed', 'Orange'],
	[218,112,214,'Orchid', 'Purple'],
	[238,232,170,'PaleGoldenrod', 'Yellow'],
	[152,251,152,'PaleGreen', 'Green'],
	[175,238,238,'PaleTurquoise', 'Cyan'],
	[219,112,147,'PaleVioletRed', 'Pink'],
	[255,239,213,'PapayaWhip', 'Yellow'],
	[255,218,185,'PeachPuff', 'Yellow'],
	[205,133,63,'Peru', 'Brown'],
	[255,192,203,'Pink', 'Pink'],
	[221,160,221,'Plum', 'Purple'],
	[176,224,230,'PowderBlue', 'Blue'],
	[128,0,128,'Purple', 'Purple'],
	[255,0,0,'Red', 'Red'],
	[188,143,143,'RosyBrown', 'Brown'],
	[65,105,225,'RoyalBlue', 'Blue'],
	[139,69,19,'SaddleBrown', 'Brown'],
	[250,128,114,'Salmon', 'Red'],
	[244,164,96,'SandyBrown', 'Brown'],
	[46,139,87,'SeaGreen', 'Green'],
	[255,245,238,'Seashell', 'White'],
	[160,82,45,'Sienna', 'Brown'],
	[192,192,192,'Silver', 'Gray and black'],
	[135,206,235,'SkyBlue', 'Blue'],
	[106,90,205,'SlateBlue', 'Purple'],
	[112,128,144,'SlateGray', 'Gray and black'],
	[255,250,250,'Snow', 'White'],
	[255,127,0,'SpringGreen', 'Green'],
	[70,130,180,'SteelBlue', 'Blue'],
	[210,180,140,'Tan', 'Brown'],
	[128,128,0,'Teal', 'Cyan'],
	[216,191,216,'Thistle', 'Purple'],
	[255,99,71,'Tomato', 'Orange'],
	[64,224,208,'Turquoise', 'Cyan'],
	[238,130,238,'Violet', 'Purple'],
	[245,222,179,'Wheat', 'Brown'],
	[255,255,255,'White', 'White'],
	[245,245,245,'WhiteSmoke', 'White'],
	[255,255,0,'Yellow', 'Yellow'],
	[154,205,50,'YellowGreen', 'Green']
]
