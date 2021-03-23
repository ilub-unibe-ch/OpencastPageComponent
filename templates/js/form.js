OpencastPageComponent = {

    max_width: 1000,

    slider: null,

    overwriteResetButton: function(name, url) {
        $('input[name="cmd[resetFilter]"]').replaceWith('<a class="btn btn-default" href="' + url + '">' + name + '</a>');
    },

    initForm: function() {
        OpencastPageComponent.slider = $("#ocpc_slider").data("ionRangeSlider");
        OpencastPageComponent.updateSlider();

        $('input#prop_size_width').change(function() {
            let new_width = $(this).val();
            if (OpencastPageComponent.keepAspectRatio()) {
                let current_width = $('#ocpc_thumbnail').width();
                let current_height = $('#ocpc_thumbnail').height();
                let ratio = (current_width / current_height);
                let new_height = new_width / ratio;
                $('#ocpc_thumbnail').height(new_height);
                $('input#prop_size_height').val(new_height);
            }
            $('#ocpc_thumbnail').width(new_width);
            OpencastPageComponent.updateSlider();
        });

        $('input#prop_size_height').change(function() {
            let new_height = $(this).val();
            if (OpencastPageComponent.keepAspectRatio()) {
                let current_width = $('#ocpc_thumbnail').width();
                let current_height = $('#ocpc_thumbnail').height();
                let ratio = (current_width / current_height);
                let new_width = new_height * ratio;
                $('#ocpc_thumbnail').width(new_width);
                $('input#prop_size_width').val(new_width);
                OpencastPageComponent.updateSlider();
            }
            $('#ocpc_thumbnail').height(new_height);
        });
    },

    updateSlider: function() {
        this.max_width = $('#ocpc_thumbnail').width() * 2;
        let width = $('input#prop_size_width').val();
        let percentage = (width / OpencastPageComponent.max_width) * 100;
        OpencastPageComponent.slider.update({from: percentage});
    },

    sliderCallback: function(data) {
        let current_width = $('#ocpc_thumbnail').width();
        let current_height = $('#ocpc_thumbnail').height();
        let ratio = (current_width / current_height);
        let percentage = data.from;

        let new_width = OpencastPageComponent.max_width * (percentage / 100);
        let new_height = (new_width / ratio);

        $('#ocpc_thumbnail').width(new_width);
        $('input#prop_size_width').val(new_width);
        $('#ocpc_thumbnail').height(new_height);
        $('input#prop_size_height').val(new_height);
    },

    keepAspectRatio: function() {
        return $('input#prop_size_constr').is(":checked");
    },
}
