var gulp = require('gulp');
var sass = require('gulp-sass');
var minify = require('gulp-minify');
var watch = require('gulp-watch');

gulp.task('sass', function() {
	gulp.src('scss/*.scss')
		.pipe(watch('scss/*.scss'))
		.pipe(sass({outputStyle:'compressed'}))
		.pipe(gulp.dest('css'));
});

gulp.task('compress', function() {
	gulp.src(['js/admin-post.js','js/conf-schedule.js','js/conf-schedule-single.js'])
		.pipe(watch(['js/admin-post.js','js/conf-schedule.js','js/conf-schedule-single.js']))
		.pipe(minify())
		.pipe(gulp.dest('js'))
});

gulp.task('default', ['sass','compress']);