<?php
namespace Drupal\players_games\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pages for players games.
 */
class PlayersGames extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ModalFormContactController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $form_builder
   *   The form builder.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function waitlist() {

    // Array to hold the game info.
    $games = [];

    // Todays time, need this to get what is the current
    // date.  If we are less than 4 am into the next day
    // the current date is yesterday.
    $time = date('G');

    // Get the correct date based on the time.
    if ($time >= 0 && $time <= 4) {
      $date = date('Y-m-d', strtotime("-1 days"));
    }
    else {
      $date = date('Y-m-d');
    }

    // Get the node based on the current date.
    $node = $this->entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'field_players_game_date' => $date
      ]);

    // If there is node, get the info about the games.
    if ($node) {

      // Get the current node, using entity type manager
      // puts all the nodes in array, we are only concerned
      // with the first and should be only node.
      $node = current($node);

      // Get data from field.
      if ($paragraph_field_items = $node->get('field_players_games')->getValue()) {

        // Get storage. It very useful for loading a small number of objects.
        $paragraph_storage = $this->entityTypeManager()->getStorage('paragraph');

        // Collect paragraph field's ids.
        $ids = array_column($paragraph_field_items, 'target_id');

        // Load all paragraph objects.
        $paragraphs_objects = $paragraph_storage->loadMultiple($ids);

        // Step through each of the paragraph items and get
        // the game info.
        foreach ($paragraphs_objects as $paragraph) {

          // Get the game type field.
          $game_type = $paragraph->field_players_tax_game_type->getValue();

          // Load the term of game type.
          $term = $this->entityTypeManager()->getStorage('taxonomy_term')->load($game_type[0]['target_id']);

          // Add the game to the games array.
          $games['games'][] = [
            'title' => $term->label(),
            'start_time' => $paragraph->field_start_time_hour->value . ' ' . $paragraph->field_start_time_am_pm->value,
            'end_time' => $paragraph->field_end_time_hour->value . ' ' . $paragraph->field_end_time_am_pm->value,
          ];
        }
      }
    }

    // Set the display date and actual date in the games array.
    $games['display_date'] = date('l F j, Y', strtotime($date));
    $games['date'] = $date;

    // Set the render array.
    return [
      '#theme' => 'players_waitlist',
      '#games' => $games,
    ];
  }

}
