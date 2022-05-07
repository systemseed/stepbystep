import React from "react";
import PropTypes from "prop-types";
import Box from "@material-ui/core/Box";
import CoursesSection from "@anu/pages/courses/Section";
import { coursePropTypes } from "@anu/utilities/transform.course";
import { courseCategoryPropTypes } from "@anu/utilities/transform.courseCategory";

const CoursesSections = ({ sections, courses }) =>
  sections.map((section) => (
    <Box px={2} key={section.id}>
      <CoursesSection
        // Filter out courses which don't belong to the category.
        courses={courses
          .filter(
            (course) =>
              course.categories &&
              course.categories.length > 0 &&
              course.categories.map((term) => term.id).includes(section.id)
          )
          .sort((a, b) => {
            // @todo: checks can be deleted after courses reorder implementation.
            if (
              typeof a.weightPerCategory === "undefined" ||
              typeof a.weightPerCategory[section.id] === "undefined" ||
              typeof b.weightPerCategory === "undefined" ||
              typeof b.weightPerCategory[section.id] === "undefined"
            ) {
              return 0;
            }
            return (
              a.weightPerCategory[section.id] - b.weightPerCategory[section.id]
            );
          })}
      />
    </Box>
  ));

CoursesSections.propTypes = {
  courses: PropTypes.arrayOf(coursePropTypes),
  sections: PropTypes.arrayOf(courseCategoryPropTypes),
};

CoursesSections.defaultProps = {
  courses: [],
  sections: [],
};

export default CoursesSections;
