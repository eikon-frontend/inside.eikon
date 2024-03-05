import { useBlockProps } from "@wordpress/block-editor";
import { withSelect, withDispatch } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import "./editor.scss";

function Edit({ attributes, setAttributes, posts, getMedia }) {
  const { selectedPosts } = attributes;

  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title.rendered }))
    : [];


  const handleSelectChange = async (event) => {
    const selectedOptions = Array.from(event.target.options)
      .filter((option) => option.selected)
      .map(async (option) => {
        // Find the full post object in the posts array
        const post = posts.find((post) => post.id === Number(option.value));

        // Fetch the media for the selected post
        if (post.featured_media) {
          const media = await getMedia(post.featured_media);
          if (media && media.source_url) {
            // Add the thumbnail URL to the post object
            post.thumbnail = media.source_url;
          }
        }

        // Return an object with the post id, title, and thumbnail (if available)
        return {
          id: post.id,
          title: post.title.rendered,
          thumbnail: post.thumbnail || null,
        };
      });

    const resolvedOptions = await Promise.all(selectedOptions);

    setAttributes({ selectedPosts: resolvedOptions });
  };

  return (
    <div {...useBlockProps()}>
      <label>
        Select Posts
        <select
          multiple
          value={selectedPosts.map((post) => post.id)}
          onChange={handleSelectChange}
        >
          {postOptions.map((option) => (
            <option
              key={option.value}
              value={option.value}
              dangerouslySetInnerHTML={{ __html: option.label }}
            />
          ))}
        </select>
      </label>
      {selectedPosts.map((post, index) => {
        return (
          <div key={index}>
            <h2 dangerouslySetInnerHTML={{ __html: post.title }} />
            {post.thumbnail && (
              <img src={post.thumbnail} alt={post.title.rendered} />
            )}
          </div>
        );
      })}
    </div>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "project", { per_page: -1 });

  return { posts };
})(
  withDispatch((dispatch, props, { select }) => {
    return {
      getMedia: (id) => select("core").getMedia(id),
    };
  })(Edit)
);
