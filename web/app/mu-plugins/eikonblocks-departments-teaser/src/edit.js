import React, { useState, useEffect } from 'react';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useDispatch, withSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";
import { PanelBody, SelectControl } from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import "./editor.scss";

// a little function to help us with reordering the result
const reorder = (list, startIndex, endIndex) => {
  const result = Array.from(list);
  const [removed] = result.splice(startIndex, 1);
  result.splice(endIndex, 0, removed);

  return result;
};

function Edit({ attributes, setAttributes, posts }) {
  const { editPost } = useDispatch('core/editor');

  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const [selectedOptions, setSelectedOptions] = useState(attributes.selectedOptions || []);

  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title?.rendered }))
    : [];

  const addProject = (value) => {
    const selectedOption = postOptions.find(option => option.value === value);
    if (selectedOption) {
      setSelectedOptions(prevOptions => [...prevOptions, selectedOption]);

      const post = posts.find((post) => post.id === Number(selectedOption.value));
      if (post) {
        setSelectedPosts(prevPosts => [...prevPosts, post]);
      }
    }
  };

  const removeProject = (postId) => {
    const newSelectedPosts = selectedPosts.filter(post => post.id !== postId);
    setSelectedPosts(newSelectedPosts);

    const newSelectedOptions = selectedOptions.filter(option => option.value !== postId);
    setSelectedOptions(newSelectedOptions);
  };

  const onDragEnd = (result) => {
    // dropped outside the list
    if (!result.destination) {
      return;
    }

    const items = reorder(
      selectedPosts,
      result.source.index,
      result.destination.index
    );

    setSelectedPosts(items);
  };

  useEffect(() => {
    setAttributes({ selectedPosts, selectedOptions });
    editPost({ meta: { selectedPosts } });
  }, [selectedPosts, selectedOptions]);

  return (
    <DragDropContext onDragEnd={onDragEnd}>
      <div {...useBlockProps()}>
        <div style={{ display: 'flex', justifyContent: 'space-between', gap: '15px' }}>
          <div style={{ width: '50%' }}>
            <h2>Liste des projets</h2>
            <div className='list-projects list-projects-available'>
              {postOptions
                .filter(option => !selectedOptions.map(post => post.value).includes(option.value))
                .map((option, index) => (
                  <div
                    key={index}
                    value={option.value}
                    className='list-projects-item'
                    onClick={() => addProject(option.value)}
                    dangerouslySetInnerHTML={{ __html: option.label }}
                  />
                ))}
            </div>
          </div>
          <div style={{ width: '50%' }}>
            <h2>Projets sélectionnés</h2>
            <Droppable droppableId="droppable">
              {(provided) => (
                <div className='list-projects list-projects-selected' {...provided.droppableProps} ref={provided.innerRef}>
                  {selectedPosts.map((post, index) => (
                    <Draggable key={post.id} draggableId={String(post.id)} index={index}>
                      {(provided) => (
                        <div
                          ref={provided.innerRef}
                          {...provided.draggableProps}
                          {...provided.dragHandleProps}
                        >
                          <div className='list-projects-item'>
                            <span dangerouslySetInnerHTML={{ __html: post.title.rendered }} />
                            <button onClick={() => removeProject(post.id)}>✖️</button>
                          </div>
                        </div>
                      )}
                    </Draggable>
                  ))}
                  {provided.placeholder}
                </div>
              )}
            </Droppable>
          </div>
        </div>
      </div>
    </DragDropContext>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "department", { per_page: -1 });

  return { posts };
})(Edit);
