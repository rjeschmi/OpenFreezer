
var engine = new Bloodhound ({
    remote: {
	url: '/OF_app/autocomplete/OFid',
    	filter: function(resp) {
       		console.debug("out: "+resp.items);
       		return $.map(resp.items, function (item) {
			return {
				value: item
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
},
{
  name: 'OF_ids',
  highlight: true,
  source: engine.ttAdapter()
});

