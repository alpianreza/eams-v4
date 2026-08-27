<div class="modal fade" id="modalAdd" tabindex="-1" aria-labelledby="modalAddLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" action="{{ route('checklist-master.question.store', $itemType) }}" class="modal-content checklist-master-modal">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="modalAddLabel">Tambah Pertanyaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="add-question">Pertanyaan</label>
                    <textarea name="question" id="add-question" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-check">
                    <input type="checkbox" name="require_photo" value="1" class="form-check-input" id="add-require-photo">
                    <label class="form-check-label" for="add-require-photo">Wajib Foto</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>
