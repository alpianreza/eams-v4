<div class="modal fade" id="modalEdit{{ $master->id }}" tabindex="-1" aria-labelledby="modalEditLabel{{ $master->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('checklist-master.question.update', $master) }}" class="modal-content checklist-master-modal">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditLabel{{ $master->id }}">Edit Pertanyaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="edit-question-{{ $master->id }}">Pertanyaan</label>
                    <textarea name="question" id="edit-question-{{ $master->id }}" class="form-control" rows="3" required>{{ $master->question }}</textarea>
                </div>

                <div class="form-check mb-2">
                    <input type="checkbox" name="require_photo" value="1" class="form-check-input" id="edit-require-photo-{{ $master->id }}" @checked($master->require_photo)>
                    <label class="form-check-label" for="edit-require-photo-{{ $master->id }}">Wajib Foto</label>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="active" value="1" class="form-check-input" id="edit-active-{{ $master->id }}" @checked($master->active)>
                    <label class="form-check-label" for="edit-active-{{ $master->id }}">Aktif</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
