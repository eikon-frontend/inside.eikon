import React, { useState, useEffect } from 'react';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useDispatch, withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
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

function Edit({ attributes, setAttributes, posts, years, sections, subjects }) {
  const { editPost } = useDispatch('core/editor');

  const [selectedPosts, setSelectedPosts] = useState(attributes.selectedPosts || []);
  const [selectedYear, setSelectedYear] = useState('');
  const [selectedSection, setSelectedSection] = useState('');
  const [selectedSubject, setSelectedSubject] = useState('');
  const [selectedOptions, setSelectedOptions] = useState(attributes.selectedOptions || []);

  const { content, backgroundColor, textColor } = attributes;

  const colors = [
    { label: 'Blue', value: 'blue' },
    { label: 'Black', value: 'black' },
    { label: 'White', value: 'white' },
    { label: 'Red', value: 'red' },
    { label: 'Orange', value: 'orange' },
    { label: 'Fuchsia', value: 'fuchsia' },
    { label: 'Pink', value: 'pink' },
    { label: 'Violet', value: 'violet' },
  ];

  const postOptions = posts
    ? posts.map((post) => ({ value: post.id, label: post.title?.rendered, yearId: post.acf?.year, sectionId: post.acf?.section, subjectId: post.acf?.subjects }))
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

  const handleYearChange = (event) => {
    setSelectedYear(event.target.value);
  };

  const handleSectionChange = (event) => {
    setSelectedSection(event.target.value);
  };

  const handleSubjectChange = (event) => {
    setSelectedSubject(event.target.value);
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
    setAttributes({ selectedPosts, selectedOptions, content, backgroundColor, textColor });
    editPost({ meta: { selectedPosts } });
  }, [selectedPosts, selectedOptions, content, backgroundColor, textColor]);

  return (
    <>
      <InspectorControls>
        <PanelBody title="Color Settings">
          <SelectControl
            label="Background Color"
            value={backgroundColor}
            options={colors}
            onChange={(value) => setAttributes({ backgroundColor: value })}
          />
          <SelectControl
            label="Text Color"
            value={textColor}
            options={colors}
            style={{ width: '100%' }}
            onChange={(value) => setAttributes({ textColor: value })}
          />
        </PanelBody>
      </InspectorControls>
      <DragDropContext onDragEnd={onDragEnd}>
        <div {...useBlockProps()} style={{ backgroundColor: backgroundColor, color: textColor }}>
          <div className="filters">
            <h2>Filtrer les projets par taxonomies</h2>
            <select value={selectedYear} onChange={handleYearChange}>
              <option value="">Années</option>
              {years && years.map((year) => (
                <option key={year.id} value={year.id}>{year.name}</option>
              ))}
            </select>
            <select value={selectedSection} onChange={handleSectionChange}>
              <option value="">Sections</option>
              {sections && sections.map((section) => (
                <option key={section.id} value={section.id}>{section.name}</option>
              ))}
            </select>
            <select value={selectedSubject} onChange={handleSubjectChange}>
              <option value="">Subjects</option>
              {subjects && subjects.map((subject) => (
                <option key={subject.id} value={subject.id}>{subject.name}</option>
              ))}
            </select>
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', gap: '5px' }}>
            <div style={{ width: '50%' }}>
              <h2>Liste des projets</h2>
              <div className='list-projects list-projects-available'>
                {postOptions
                  .filter(option => selectedYear == "" || option.yearId == selectedYear)
                  .filter(option => selectedSection == "" || option.sectionId == selectedSection)
                  .filter(option => selectedSubject == "" || option.subjectId == selectedSubject)
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
              <h2>Selected project</h2>
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
                              <img className='image-thumbnail' src={post.featured_image_src} alt={post.title.rendered} />
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
    </>
  );
}

export default withSelect((select) => {
  const { getEntityRecords } = select("core");

  const posts = getEntityRecords("postType", "project", { per_page: -1 });
  const years = getEntityRecords("taxonomy", "year", { per_page: -1 });
  const sections = getEntityRecords("taxonomy", "section", { per_page: -1 });
  const subjects = getEntityRecords("taxonomy", "subjects", { per_page: -1 });

  return { posts, years, sections, subjects };
})(Edit);
