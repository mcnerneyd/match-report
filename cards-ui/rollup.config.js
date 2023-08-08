// vim: expandtab:ts=2:sw=2:ai
import serve from "rollup-plugin-serve";
import livereload from "rollup-plugin-livereload";
import babel from '@rollup/plugin-babel';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';
import builtins from 'rollup-plugin-node-builtins';
import globals from 'rollup-plugin-node-globals';
import scss from 'rollup-plugin-scss';

export default {
  input: "src/index.js",
  output: {
    file: "dist/bundle.js",
    format: "iife",
    sourcemap: true,
  },
  plugins: [
    scss({ 
      fileName: 'bundle.css', 
      include: ['node_modules/**/*.css','src/*.scss'],
      verbose: true 
    }),
    nodeResolve({
      extensions: [".js"],
    }),
    replace({
      'process.env.NODE_ENV': JSON.stringify( 'development' ),
      preventAssignment: true
    }),
    babel({
      presets: [
          ["@babel/preset-react", {"runtime":"automatic"}]
      ],
      babelHelpers: 'bundled'
    }),
    commonjs(),
    /*serve({
      open: true,
      verbose: true,
      contentBase: ["", "public"],
      host: "localhost",
      port: 3000,
    }),*/
    //livereload({ watch: "dist" }),
  ]
};
