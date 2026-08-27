<?php

namespace Tests\Feature\Ui;

use App\Support\Ui\StatusPresentation;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesignSystemPrimitivesTest extends TestCase
{
    public function test_form_and_action_primitives_render_with_eams_prefix(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.button variant="primary" icon="check">Simpan</x-ui.button>
            <x-ui.input name="asset_code" label="Asset Code" />
            <x-ui.textarea name="note" label="Catatan">Isi</x-ui.textarea>
            <x-ui.select name="status" label="Status"><option>Good</option></x-ui.select>
            <x-ui.checkbox name="active" label="Aktif" />
            <x-ui.radio name="state" value="ok" label="OK" />
            <x-ui.switch name="enabled" label="Enabled" />
        BLADE);

        $this->assertStringContainsString('eams:bg-brand', $html);
        $this->assertStringContainsString('data-eams-component="input"', $html);
        $this->assertStringContainsString('data-eams-component="switch"', $html);
        $this->assertStringContainsString('Asset Code', $html);
    }

    public function test_canonical_statuses_have_centralized_presentations(): void
    {
        foreach (['OK', 'NOT_OK', 'NA', 'DONE', 'OPEN', 'LATE', 'FUTURE', 'HOLIDAY', 'GOOD', 'NEED_REPAIR', 'NOT_ACTIVE', 'VALID', 'NEAR_EXPIRY', 'EXPIRED'] as $status) {
            $presentation = StatusPresentation::for($status);

            $this->assertSame($status, $presentation['key']);
            $this->assertNotSame('', $presentation['label']);
            $this->assertContains($presentation['tone'], ['success', 'warning', 'danger', 'info', 'neutral']);
        }
    }

    public function test_status_indicator_uses_semantic_tone(): void
    {
        $html = Blade::render('<x-ui.status-indicator status="need_repair" />');

        $this->assertStringContainsString('data-status="NEED_REPAIR"', $html);
        $this->assertStringContainsString('Need Repair', $html);
        $this->assertStringContainsString('eams:bg-warning-soft', $html);
    }
}
