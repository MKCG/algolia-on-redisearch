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

const renderHits = (renderOptions, isFirstRender) => {
  const { hits, widgetParams } = renderOptions;

  document.querySelector(widgetParams.container).innerHTML = `
    <table class="mdl-data-table mdl-js-data-table">
      <thead>
        <tr>
          <th class="mdl-data-table__cell--non-numeric">Titre</th>
          <th>Année de Début</th>
          <th>Année de Fin</th>
          <th>Durée (minutes)</th>
        </tr>
      </thead>
      <tbody>
        ${hits
          .map(
            item =>
              `<tr>
                <td>${item.primaryTitle}</td>
                <td>${item.startYear}</td>
                <td>` + (item.endYear ? item.endYear : item.startYear) + `</td>
                <td>${item.runtimeMinutes}</td>
              </tr>`
          )
          .join('')}
      </tbody>
    </table>
  `;
};

const renderRefinementList = (renderOptions, isFirstRender) => {
  const { items , refine } = renderOptions;

  let container = document.querySelector(renderOptions.widgetParams.container);

  container.innerHTML = `
    <ul class="mdl-list facet-terms">
      ${items
        .map(
          (item, idx) => `
            <li
              class="mdl-list__item"
              data-value="${item.value}"
            >
              <span class="mdl-list__item-secondary-action">
                <label
                  class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                  for="facet-${renderOptions.widgetParams.attribute}-${idx}">
                  <input
                    id="facet-${renderOptions.widgetParams.attribute}-${idx}"
                    type="checkbox"
                    class="mdl-checkbox__input"`
                    + (item.isRefined ? ` checked`: ``) + `
                   />
                </label>
              </span>
              <span class="mdl-list__item-primary-content">
                ${item.label} (${item.count})
              </span>
            </li>`
        )
        .join('')}
    </ul>
  `;

  [...container.querySelectorAll('li')].forEach(element => {
    element.addEventListener('click', event => {
      refine(event.currentTarget.dataset.value);
    });
  });
};

const renderCurrentRefinements = (renderOptions, isFirstRender) => {
  const { items , refine } = renderOptions;

  const createDataAttribtues = refinement =>
    Object.keys(refinement)
      .map(key => `data-${key}="${refinement[key]}"`)
      .join(' ');

  let container = document.querySelector(renderOptions.widgetParams.container);

  let refinements = items.map((item) => {
    return item.refinements.map((refinement, idx) => `
        <span
          class="mdl-chip mdl-chip--deletable facet-refinement"
          ${createDataAttribtues(refinement)}
        >
          <span class="mdl-chip__text">${refinement.label}</span>
          <button type="button" class="mdl-chip__action"><i class="material-icons">cancel</i></button>
        </span>
      `).join('')
  });

  container.innerHTML = refinements.join('');

  [...container.querySelectorAll('.facet-refinement')].forEach(element => {
    element.addEventListener('click', event => {
      const item = Object.keys(event.currentTarget.dataset).reduce(
        (acc, key) => ({
          ...acc,
          [key]: event.currentTarget.dataset[key],
        }),
        {}
      );

      refine(item);
    });
  });
};

const customHits = instantsearch.connectors.connectHits(renderHits);

const customRefinementList = instantsearch.connectors.connectRefinementList(
  renderRefinementList
);

const currentRefinements = instantsearch.connectors.connectCurrentRefinements(
  renderCurrentRefinements
);

search.addWidgets([
 instantsearch.widgets.searchBox({
      container: '#searchbox',
      placeholder: 'Rechercher...',
      showSubmit: false,
      showReset: false,
      showLoadingIndicator: false
  }),

  currentRefinements({
    container: '#current-refinements',
  }),

  customRefinementList({
    container: '#facet-titleType',
    attribute: 'titleType',
  }),

  customRefinementList({
    container: '#facet-genres',
    attribute: 'genres',
  }),

  instantsearch.widgets.hitsPerPage({
    container: '#hits-per-page',
    items: [
      { label: '20 hits per page', value: 20, default: true },
      { label: '50 hits per page', value: 50 },
      { label: '100 hits per page', value: 100 },
    ],
  }),

  customHits({
    container: '#hits',
  }),
]);

search.start();
