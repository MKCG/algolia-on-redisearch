/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../css/app.css';

// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
// import $ from 'jquery';

console.log('Hello Webpack Encore! Edit me in assets/js/app.js');

const algoliasearch = require('algoliasearch');
// const instantsearch = require('instantsearch.js');

const client = algoliasearch(
    'YourApplicationID',
    'YourAdminAPIKey',
    { hosts: [ { protocol: 'http', url: '127.0.0.1:8100/search' } ] }
);

const search = instantsearch({
    indexName: 'imdb_titles',
    searchClient: client,
});


search.addWidgets([
  instantsearch.widgets.currentRefinements({
    container: '#current-refinements',
  }),

  instantsearch.widgets.refinementList({
    container: '#facet-titleType',
    attribute: 'titleType',
  }),

  instantsearch.widgets.refinementList({
    container: '#facet-genres',
    attribute: 'genres',
  }),

  instantsearch.widgets.hitsPerPage({
    container: '#hits-per-page',
    items: [
      { label: '8 hits per page', value: 8, default: true },
      { label: '16 hits per page', value: 16 },
    ],
  }),

  instantsearch.widgets.hits({
    container: '#hits',
    templates: {
      item: `
          <span class="hit-name">{{ primaryTitle }}</span>
          <span class="hit-startYear">{{ startYear }}</span>
          <span class="hit-endYear">{{ endYear }}</span>
          <span class="hit-runtimeMinutes">{{ runtimeMinutes }}</span>
      `,
    },
  })
]);

search.addWidget(
    instantsearch.widgets.searchBox({
        container: '#searchbox'
    })
);

search.start();
