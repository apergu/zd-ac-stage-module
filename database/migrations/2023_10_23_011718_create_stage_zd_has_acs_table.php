<?php

use App\Models\AcStage;
use App\Models\ZdStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('stage_zd_has_acs', function (Blueprint $table) {
      $table->foreignIdFor(ZdStage::class);
      $table->foreignIdFor(AcStage::class);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('stage_zd_has_acs');
  }
};
