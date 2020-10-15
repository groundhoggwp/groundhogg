import HeaderPrimary from './header-primary';
import HeaderSecondary from './header-secondary';

function HeaderToolbar() {

	return (
		<NavigableToolbar
			className="groundhogg-header-toolbar edit-post-header-toolbar"
			aria-label={ toolbarAriaLabel }
		>
			<div className="groundhogg-header-toolbar__left edit-post-header-toolbar__left">
				<HeaderPrimary />
				<HeaderSecondary />
			</div>
		</NavigableToolbar>
	);
}

export default HeaderToolbar;