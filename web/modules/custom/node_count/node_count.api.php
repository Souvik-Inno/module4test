<?php

/**
 * @file
 * Hooks specific for the node_count module.
 */

/**
 * Respond to node view count being incremented.
 *
 * This hook allows module to respond whenever the total number of times
 * the current user has viewed a specific node during their current session
 * is increased.
 *
 * @param int $current_count
 *   The number of times current user has viewed the node during this session.
 * @param \Drupal\node\NodeInterface $node
 *   The node being viewed.
 */
function hook_node_count_count_incremented($current_count, \Drupal\node\NodeInterface $node) {
  // Display message if user views node first time.
  if ($current_count === 1) {
    \Drupal::messenger()->addMessage(t('Thank you for viewing the %title for the first time', ['%title' => $node->label()]));
  }
}
