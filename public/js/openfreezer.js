
var engine = new Bloodhound ({
    remote: {
	url: '/OF_app/autocomplete/OFid',
    	filter: function(resp) {
       		console.debug("out: "+resp.items);
       		return $.map(resp.items, function (item) {
			return {
                id: item.id,
				value: item.value
			};
		});
    	}
    },
    datumTokenizer: function(d) {
       return Bloodhound.tokenizers.whitespace(d.value);
    },
    queryTokenizer: Bloodhound.tokenizers.whitespace
});
engine.initialize();

 
$('.typeahead').typeahead({
  highlight:true,
},
{
  name: 'OF_ids',
  displayKey: 'id',
  source: engine.ttAdapter(),
  templates: {
    suggestion: Handlebars.compile('<p>{{id}} - {{value}}</p>')
  }
});

