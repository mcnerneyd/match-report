import serve from "rollup-plugin-serve";
import livereload from "rollup-plugin-livereload";
import babel from '@rollup/plugin-babel';
import scss from 'rollup-plugin-scss';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';
import image from 'rollup-plugin-img';

export default {
  input: "src/index.js",
  output: {
    file: "dist/bundle.js",
    format: "iife",
    sourcemap: true,
  },
  plugins: [
    scss({
      include: ["/**/*.css", "/**/*.scss", "/**/*.sass"],
      failOnError: true,
      }),
    nodeResolve({
      extensions: [".js"],
    }),
    replace({
      'process.env.NODE_ENV': JSON.stringify( 'development' ),
      preventAssignment: true
    }),
    babel({
      presets: ["@babel/preset-react"],
    }),
    commonjs(),
    image({
      limit: 10000
    }),
    serve({
      open: true,
      openPage: "/",
      verbose: true,
      contentBase: "",
      historyApiFallback: true,
      host: "localhost",
      port: 3000,
    }),
    livereload({ watch: "dist" }),
  ]
};

