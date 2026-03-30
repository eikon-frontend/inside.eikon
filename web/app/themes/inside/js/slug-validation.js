(function ($) {
  "use strict";

  // Hook into the permalink "OK" button click
  $(document).on("click", "#edit-slug-buttons .save", function () {
    var $input = $("#new-post-slug");
    var newSlug = $input.val();

    if (!newSlug) {
      return;
    }

    $.post(eikonSlug.ajaxUrl, {
      action: "eikon_check_project_slug",
      nonce: eikonSlug.nonce,
      slug: newSlug,
      post_id: eikonSlug.postId,
    }).done(function (response) {
      if (response.success && response.data.exists) {
        $("#edit-slug-box .slug-warning").remove();
        $("#edit-slug-box").append(
          '<p class="slug-warning" style="color: #d63638; margin: 4px 0 0; font-style: italic;">' +
          "⚠ Ce slug est déjà utilisé par un autre projet. Il sera automatiquement modifié à l'enregistrement." +
          "</p>"
        );
      }
    });
  });

  // Clean up warning when editing slug again
  $(document).on("click", "#edit-slug-buttons .edit-slug", function () {
    $("#edit-slug-box .slug-warning").remove();
  });
})(jQuery);
