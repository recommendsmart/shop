<?php

namespace Drupal\Tests\pbf\Functional;

/**
 * Test access permissions with Pbf field which reference node.
 *
 * @group pbf
 */
class PbfAccessByNodeRefTest extends PbfBaseTest {

  /*
   * Field name to add.
   *
   * @var string
   */
  protected $fieldname;

  /**
   * Setup and create content whith Pbf field.
   */
  public function setUp(): void {
    parent::setUp();

    $this->fieldname = 'field_pbf_group';
    $this->attachPbfNodeFields($this->fieldname);

    $this->article1 = $this->createSimpleArticle('Article 1', $this->fieldname, $this->group1->id(), 1, 0, 0, 0);
    $this->article2 = $this->createSimpleArticle('Article 2', $this->fieldname, $this->group1->id(), 0, 1, 0, 0);

  }

  /**
   * Test the "pbf" node access with a Pbf field which reference node.
   */
  public function testPbfAccessByNodeRef() {

    $this->drupalLogin($this->adminUser);

    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertSession()->statusCodeEquals(200, 'adminUser is allowed to view the content.');
    $this->drupalGet("node/{$this->article1->id()}/edit");
    // Make sure we don't get a 401 unauthorized response:
    $this->assertSession()->statusCodeEquals(200, 'adminUser is allowed to edit the content.');

    $bundle_path = 'admin/structure/types/manage/article';
    // Check that the field appears in the overview form.
    $this->drupalGet($bundle_path . '/fields');
    $this->assertSession()->elementExists('xpath', '//table[@id="field-overview"]//tr[@id="field-pbf-group"]/td[1]');

    // Check that the field appears in the overview manage display form.
    $this->drupalGet($bundle_path . '/form-display');
    $this->assertSession()->elementExists('xpath', '//table[@id="field-display-overview"]//tr[@id="field-pbf-group"]/td[1]');
    $this->assertSession()->fieldValueEquals('fields[field_pbf_group][type]', 'pbf_widget');

    // Check that the field appears in the overview manage display page.
    $this->drupalGet($bundle_path . '/display');
    $this->assertSession()->elementExists('xpath', '//table[@id="field-display-overview"]//tr[@id="field-pbf-group"]/td[1]');
    $this->assertSession()->fieldValueEquals('fields[field_pbf_group][type]', 'pbf_formatter_default');

    $user_path_config = 'admin/config/people/accounts';
    $this->drupalGet($user_path_config . '/fields');
    $this->assertSession()->elementExists('xpath', '//table[@id="field-overview"]//tr[@id="field-pbf-group"]/td[1]');
    $this->drupalGet($user_path_config . '/form-display');
    $this->assertSession()->fieldValueEquals('fields[field_pbf_group][type]', 'pbf_widget');
    $this->drupalGet($user_path_config . '/display');
    $this->assertSession()->fieldValueEquals('fields[field_pbf_group][type]', 'pbf_formatter_default');

    // Test view access with normal user.
    $this->drupalLogin($this->normalUser);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->pageTextContains(t('Access denied'));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet("node/{$this->article1->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);

    // Build the search index.
    $this->container->get('cron')->run();
    // Check to see that we find the number of search results expected.
    $this->checkSearchResults('Article', 1);

    // Set article2 as public without custom permission.
    $value = [
      'target_id' => $this->group1->id(),
      'grant_public' => 1,
      'grant_view' => 0,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->checkSearchResults('Article', 2);

    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Set article2 with view permission.
    $value = [
      'target_id' => $this->group1->id(),
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->statusCodeEquals(403);
    $this->checkSearchResults('Article', 1);

    // Associate normalUser with group1.
    $this->setUserField($this->normalUser->id(), $this->fieldname, ['target_id' => $this->group1->id()]);

    // Check if user is well associated with group1.
    $this->drupalGet("user/{$this->normalUser->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('field_pbf_group[0][target_id]', $this->group1->getTitle() . ' (' . $this->group1->id() . ')');
    $this->drupalGet("user/{$this->normalUser->id()}");
    $this->assertSession()->linkExists($this->group1->getTitle());
    $this->assertSession()->statusCodeEquals(200);

    // Check search.
    $this->container->get('cron')->run();
    $this->checkSearchResults('Article', 2);
    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->statusCodeEquals(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertSession()->statusCodeEquals(403);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertSession()->statusCodeEquals(403);

    // Set article2 with view, update, delete permissions.
    $value = [
      'target_id' => $this->group1->id(),
      'grant_public' => 0,
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
    ];
    $this->article2->set($this->fieldname, $value)->save();
    // Check view.
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->statusCodeEquals(200);
    // Check edit.
    $this->drupalGet("node/{$this->article2->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    // Check delete.
    $this->drupalGet("node/{$this->article2->id()}/delete");
    $this->assertSession()->statusCodeEquals(200);

    // Test with anonymous user.
    $this->drupalLogout();
    $this->drupalGet("node/{$this->article1->id()}");
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet("node/{$this->article2->id()}");
    $this->assertSession()->statusCodeEquals(403);

  }

}
