import { transformCourse } from "@anu/utilities/transform.course";
import { transformCourseCategory } from "@anu/utilities/transform.courseCategory";
import * as fields from "@anu/utilities/fields";

const transformCoursesPage = ({ data }) => {
  const node = data.courses_page || {};

  const courses = fields
    .getArrayValue(data, "courses")
    .map((item) => transformCourse(item, []));

  // Unlock the course if previous one is completed. This is useful when the course
  // is completed offline and a user was redirected back to the sessions page.
  for (let i = courses.length - 1; i >= 1; i--) {
    const prev = i - 1;
    if (courses[i].isLocked && courses[prev].progress_percent === 100) {
      courses[i].isLocked = false;
    }
  }

  return {
    title: fields.getTextValue(node, "title"),
    description: fields.getTextValue(node, "body"),
    courses,
    sections: fields
      .getArrayValue(node, "field_courses_content")
      .flatMap((section) =>
        fields
          .getArrayValue(section, "field_course_category")
          .map((category) => transformCourseCategory(category))
      ),
  };
};

export { transformCoursesPage };
