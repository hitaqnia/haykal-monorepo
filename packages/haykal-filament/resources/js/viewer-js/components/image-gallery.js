import Viewer from 'viewerjs';

export default function imageGallery({ id }) {
    return {
        init() {
            new Viewer(document.getElementById(id));
        }
    }
}
