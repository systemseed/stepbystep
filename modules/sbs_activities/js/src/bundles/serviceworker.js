import { setCacheNameDetails, cacheNames } from "workbox-core";
import { registerRoute } from "workbox-routing";
import { CacheFirst, NetworkFirst } from "workbox-strategies";
import { RangeRequestsPlugin } from "workbox-range-requests";
import { CacheableResponsePlugin } from "workbox-cacheable-response";
import { precacheAndRoute } from "workbox-precaching";
import { warmStrategyCache } from "workbox-recipes";

const ignoreQueryStringPlugin = {
  cacheKeyWillBeUsed: async ({ request }) => {
    const url = new URL(request.url);
    return url.pathname;
  },
};

// Customised service worker with offline activities support.
// We put customisations above default Anu routes to be able to override them.
if (
  drupalSettings &&
  drupalSettings.urls &&
  drupalSettings.current_cache &&
  drupalSettings.user_id
) {
  // Use separate cache for Workbook but append Drupal PWA cache version.
  // We cannot mix Workbox and non-Workbox cache in one cache bin because
  // Workbox will try to clean up any unknown URLs.
  // We also include current user id in cache name to avoid serving cache from
  // previous user if multiple users use the same browser.
  setCacheNameDetails({
    suffix: `${drupalSettings.current_cache}-u${drupalSettings.user_id}`,
  });

  const strategy = new NetworkFirst({
    matchOptions: {
      ignoreVary: true,
      ignoreSearch: true,
    },
    plugins: [
      ignoreQueryStringPlugin,
      new CacheableResponsePlugin({ statuses: [200] }),
    ],
  });
  registerRoute(
    ({ url }) => drupalSettings.urls.includes(url.pathname),
    strategy
  );

  warmStrategyCache({ urls: drupalSettings.urls, strategy });

  let audioUrls = [];

  if (drupalSettings.activity_audios && drupalSettings.activity_audios.length) {
    const audioUrlsWithRevisions = drupalSettings.activity_audios;
    audioUrls = audioUrlsWithRevisions.map((item) => item.url);
    // Before precaching audio URLs, apply range request plugin to them.
    // It will allow playing different types of audio from cache.
    // See https://developers.google.com/web/tools/workbox/guides/advanced-recipes#cached-av
    registerRoute(
      ({ url }) => audioUrls.includes(url.pathname),
      new CacheFirst({
        // Use the same cache bin as workbox precacheAndRoute() uses.
        cacheName: cacheNames.precache,
        matchOptions: {
          ignoreVary: true,
          ignoreSearch: true,
        },
        plugins: [
          new CacheableResponsePlugin({ statuses: [200] }),
          new RangeRequestsPlugin(),
        ],
      })
    );

    // Note that we need to pass local variable rather than reference to drupalSettings!
    // @see https://stackoverflow.com/a/64933346/1145337
    precacheAndRoute(audioUrlsWithRevisions, {
      ignoreURLParametersMatching: [/.*/],
    });
  }

  // Default Anu audio route (uses standard Anu cache).
  registerRoute(
    ({ url, request }) =>
      request.destination === "audio" && !audioUrls.includes(url.pathname),
    new CacheFirst({
      cacheName: drupalSettings.current_cache,
      matchOptions: {
        ignoreVary: true,
        ignoreSearch: true,
      },
      plugins: [
        new CacheableResponsePlugin({ statuses: [200] }),
        new RangeRequestsPlugin(),
      ],
    })
  );
}
