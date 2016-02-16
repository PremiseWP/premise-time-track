/**
 * Grunt
 *
 * @see http://gruntjs.com/api/grunt to learn more about how grunt works
 * @since  1.0
 */

module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		watch: {
			options: {
				livereload: true,
			},
			css: {
				files: ['css/source/*.css'],
				tasks: ['cssmin'],
				options: {
					livereload: true
				},
			},
			js: {
				files: ['js/source/*.js'],
				tasks: ['uglify'],
				options: {
					livereload: true
				},
			},
			livereload: {
				// reload page when css, js, images or php files changed
				files: ['css/*.css', 'js/*.js', 'img/**/*.{png,jpg,jpeg,gif,webp,svg}', '**/*.php', '**/*.html']
			},
		},

		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
			},
			my_target: {
				files: {
					'js/<%= pkg.name %>.min.js': ['js/source/*.js']
				}
			}
		},

		autoprefixer: {
			options: {
				browsers: ['last 2 versions']
			},
			multiple_files: {
                expand: true,
                flatten: true,
                src: 'css/source/*.css',
                dest: 'css/source/'
            }
		},

		cssmin: {
			options: {
				shorthandCompacting: false,
				roundingPrecision: -1
			},
			target: {
				files: {
					'css/<%= pkg.name %>.min.css': ['css/source/*.css']
				}
			}
		}
		
	});

	/**
	 * Load all plugins required
	 */
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-cssmin');

	// Default task(s).
	grunt.registerTask( 'default', ['watch'] );

};