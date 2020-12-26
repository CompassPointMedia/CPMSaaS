/*
standard.js
Use this file for library-related coding (jQuery and VueJS for example).
 */


$(document).ready(function(){

	$('[data-toggle="tooltip"]').tooltip();
	$('[data-toggle="popover"]').popover();

	$(" .scaleable ").addClass(" img-responsive ");


	$(function(){
		$(" #myCarousel.slide ").carousel({
			interval: 5000,
			pause: "hover"
		});

		$(" #myCarousel ").mouseover(function(){
			$(" #myCarousel ").carousel("pause");
		}).mouseout(function() {
			$(" #myCarousel ").carousel("cycle");
		});
	});

});


$( document ).ajaxStart(function() {
	$(" #spinner ").show();
})

$( document ).ajaxStop(function() {
	$(" #spinner ").hide();
})


Vue.component('cpm-pie-chart', {
	props: ['dataset', 'valuecolumn', 'labelcolumn', 'hover'],
	template: '<span>' +
	'<svg viewBox="-1 -1 2 2" style="transform: rotate(-90deg)">' +
	'<path v-for="(data, index) in dataset" :d="drawSection(data, index)" :fill="data.color" @mouseover="setHighlight(index) & hover($event)" @mouseout="setHighlight(null)"></path>' +
	'</svg>' +
	'</span>',
	data: function(){
		return {
			radius: .90,
			highlightIndex: null,
			highlightRadius: .95,
			nominalStart: 0,
            labelcolumn: 'label',
            valuecolumn: 'value',
		}
	},
	created: function(){
	    /* this would be useful if we needed to declare colors but ideally the colors are passed already - since other things might display those colors outside this component */
	    return false;

		var m = '', md5s = [], keys = {}
		for(var i in this.dataset){
			if(this.dataset[i].color) continue;
			m = md5(String(this.dataset[i][this.labelcolumn]).toLowerCase());
			md5s.push(m);
			keys[m] = i;
		}
		if(md5s.length){
			md5s.sort();
			var offset = 50, ref;
			for(var j in md5s){
				ref = keys[md5s[j]];
				this.dataset[ref].color = x11colors[ offset % x11colors.length ][3];
				offset++;
			}
		}
	},
	methods: {
		setHighlight: function(index){
			this.highlightIndex = index;
		},
		drawSection: function(data, index){
            /* clumsy, needs moved */
			var i, total = 0;
			for(i in this.dataset){
				total += parseInt(this.dataset[i][this.valuecolumn]);
			}
			var radius = this.radius;
			if(this.highlightIndex == index) radius = this.highlightRadius;
			var start = this.getCoordinatesForPercent(this.runningCoords[index], radius);
			var end = this.getCoordinatesForPercent(this.runningCoords[index] + (parseInt(data[this.valuecolumn]) / total), radius);
			var largeArcFlag = (parseInt(data[this.valuecolumn]) / total) > 0.5 ? 1 : 0;
			//console.log(start,end, largeArcFlag);
			return [
				'M ' + start.join(' '), // Move
				'A ' + radius + ' ' + radius +' 0 ' + largeArcFlag + ' 1 ' + end.join(' '), // Arc
				'L 0 0', // Line
			].join(' ');
		},
		getCoordinatesForPercent: function(pct, radius) {
			const x = radius * Math.cos(2 * Math.PI * pct);
			const y = radius * Math.sin(2 * Math.PI * pct);
			return [x, y];
		}
	},
	computed: {
		runningCoords: function () {
			var start = this.nominalStart;
			var i, total = 0;
			for(i in this.dataset){
				total += parseInt(this.dataset[i][this.valuecolumn]);
			}
			var self = this;
			return this.dataset.map(function(data) {
				var _return = start;
				start += (parseInt(data[self.valuecolumn]) / total);
				return _return;
			});
		},
	},
});